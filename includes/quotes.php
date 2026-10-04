<?php
require_once __DIR__ . '/functions.php';

/** Return active calculator options without exposing administrative fields. */
function get_quote_pricing_options(): array
{
    try {
        return get_db()->query(
            'SELECT service_id, pricing_key, label, category, unit, base_price, unit_price, currency
             FROM service_pricing WHERE is_active = 1 ORDER BY category, label'
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/** Calculate a transparent estimate from validated request inputs. */
function calculate_quote_estimate(array $pricing, array $input): array
{
    $quantity = max(1, min(1000, (int) ($input['quantity'] ?? 1)));
    $hours = max(1, min(24, (int) ($input['hours'] ?? 1)));
    $days = max(1, min(366, (int) ($input['days'] ?? 1)));
    $frequencyMultiplier = [
        'one_off' => 1,
        'weekly' => 4,
        'monthly' => 1,
    ][$input['frequency'] ?? 'one_off'] ?? 1;

    $unit = (string) ($pricing['unit'] ?? 'flat');
    $base = (float) ($pricing['base_price'] ?? 0);
    $unitPrice = (float) ($pricing['unit_price'] ?? 0);
    $amount = $base;
    if ($unit === 'guard_hour') {
        $amount += $unitPrice * $quantity * $hours * $days;
    } elseif ($unit === 'room') {
        $amount += $unitPrice * $quantity * $frequencyMultiplier;
    } elseif ($unit !== 'flat') {
        $amount += $unitPrice * $quantity;
    }

    return [
        'amount' => round($amount, 2),
        'currency' => $pricing['currency'] ?? 'ZMW',
        'quantity' => $quantity,
        'hours' => $hours,
        'days' => $days,
        'frequency' => $input['frequency'] ?? 'one_off',
    ];
}

function create_quote_request(array $data, array $estimate): string
{
    $db = get_db();
    $requestNumber = 'REQ-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    $stmt = $db->prepare(
        'INSERT INTO quote_requests
            (request_number, service_id, pricing_key, name, email, phone, location, requirements, estimated_amount, currency)
         VALUES (:number, :service_id, :pricing_key, :name, :email, :phone, :location, :requirements, :amount, :currency)'
    );
    $stmt->execute([
        'number' => $requestNumber,
        'service_id' => $data['service_id'] ?: null,
        'pricing_key' => $data['pricing_key'],
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'location' => $data['location'],
        'requirements' => json_encode($data['requirements'], JSON_THROW_ON_ERROR),
        'amount' => $estimate['amount'],
        'currency' => $estimate['currency'],
    ]);
    return $requestNumber;
}