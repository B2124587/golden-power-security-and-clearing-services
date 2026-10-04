document.addEventListener('DOMContentLoaded', function () {

  // ── Mobile nav toggle ────────────────────────────────────────────────────
  var toggle = document.getElementById('navToggle');
  var links  = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    links.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ── Scroll progress bar ──────────────────────────────────────────────────
  var progressBar = document.getElementById('scrollProgress');
  if (progressBar) {
    var updateProgress = function () {
      var doc = document.documentElement;
      var scrollTop = window.scrollY || doc.scrollTop;
      var height = doc.scrollHeight - doc.clientHeight;
      var pct = height > 0 ? (scrollTop / height) * 100 : 0;
      progressBar.style.width = pct + '%';
    };
    updateProgress();
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
  }

  // ── Sticky navigation state ---------------------------------------------
  var siteNav = document.getElementById('siteNav');
  if (siteNav) {
    var updateNavState = function () {
      siteNav.classList.toggle('scrolled', window.scrollY > 18);
    };
    updateNavState();
    window.addEventListener('scroll', updateNavState, { passive: true });
  }

  // ── Reveal-on-scroll for sections marked [data-reveal] ───────────────────
  var revealEls = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // ── Animated stat counters (count up when scrolled into view) ───────────
  var counterGroups = document.querySelectorAll('[data-counters]');
  if (counterGroups.length) {
    var animateCounters = function (group) {
      var nums = group.querySelectorAll('[data-count-to]');
      nums.forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
        var suffix = el.getAttribute('data-count-suffix') || '';
        var duration = 1400;
        var start = null;
        var step = function (ts) {
          if (!start) start = ts;
          var progress = Math.min((ts - start) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.round(eased * target) + suffix;
          if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
      });
    };
    if ('IntersectionObserver' in window) {
      var counterIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCounters(entry.target);
            counterIO.unobserve(entry.target);
          }
        });
      }, { threshold: 0.4 });
      counterGroups.forEach(function (g) { counterIO.observe(g); });
    } else {
      counterGroups.forEach(animateCounters);
    }
  }

  // ── Contact page: step-by-step inquiry wizard ────────────────────────────
  var wizard = document.getElementById('inquiryWizard');
  if (wizard) {
    var steps = wizard.querySelectorAll('.wizard-step');
    var dots = document.querySelectorAll('#wizardProgress span');
    var summaryBox = document.getElementById('wizardSummary');

    var goToStep = function (n) {
      steps.forEach(function (step) {
        step.classList.toggle('is-active', parseInt(step.getAttribute('data-step'), 10) === n);
      });
      dots.forEach(function (dot, i) {
        dot.classList.toggle('done', i + 1 < n);
        dot.classList.toggle('current', i + 1 === n);
      });
      if (n === 3 && summaryBox) {
        var serviceVal = document.getElementById('wizardServiceInput').value;
        var urgencyVal = document.getElementById('wizardUrgencyInput').value;
        summaryBox.innerHTML = '';
        [serviceVal, urgencyVal].forEach(function (val) {
          if (!val) return;
          var chip = document.createElement('span');
          chip.className = 'wizard-chip';
          chip.textContent = val;
          summaryBox.appendChild(chip);
        });
      }
    };

    wizard.querySelectorAll('[data-wizard-choices]').forEach(function (group) {
      var targetInput = document.getElementById(group.getAttribute('data-target'));
      var nextStep = parseInt(group.getAttribute('data-next'), 10);
      group.querySelectorAll('.wizard-choice').forEach(function (btn) {
        btn.addEventListener('click', function () {
          group.querySelectorAll('.wizard-choice').forEach(function (b) { b.classList.remove('selected'); });
          btn.classList.add('selected');
          targetInput.value = btn.getAttribute('data-value');
          window.setTimeout(function () { goToStep(nextStep); }, 220);
        });
      });
    });

    wizard.querySelectorAll('[data-back]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        goToStep(parseInt(btn.getAttribute('data-back'), 10));
      });
    });

    var initialService = document.getElementById('wizardServiceInput').value;
    if (initialService) {
      var matchingService = wizard.querySelector('.wizard-choice[data-value="' + CSS.escape(initialService) + '"]');
      if (matchingService) {
        matchingService.classList.add('selected');
        goToStep(2);
      }
    }
  }

  // ── Testimonial carousel — auto-rotate + dot navigation ──────────────────
  var testimonialWrap = document.querySelector('[data-testimonials]');
  if (testimonialWrap) {
    var slides = testimonialWrap.querySelectorAll('.testimonial-slide');
    var dots = testimonialWrap.querySelectorAll('.testimonial-dots button');
    var current = 0;
    var showSlide = function (idx) {
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
      current = idx;
    };
    var nextSlide = function () { showSlide((current + 1) % slides.length); };
    var rotateTimer = null;
    var startRotation = function () {
      if (slides.length > 1) {
        rotateTimer = window.setInterval(nextSlide, 6000);
      }
    };
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        showSlide(parseInt(dot.getAttribute('data-slide-to'), 10));
        if (rotateTimer) { window.clearInterval(rotateTimer); startRotation(); }
      });
    });
    startRotation();
  }

  // ── Gallery — staggered card entrance ────────────────────────────────────
  var cards = document.querySelectorAll('.gallery-card');
  if (cards.length && 'IntersectionObserver' in window) {
    var cardIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          cardIO.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    cards.forEach(function (card) { cardIO.observe(card); });
  } else {
    cards.forEach(function (card) { card.classList.add('in-view'); });
  }

  // ── Gallery — filter tabs ─────────────────────────────────────────────────
  var tabs    = document.querySelectorAll('.gallery-tab');
  var allCards = document.querySelectorAll('.gallery-card[data-type]');

  function applyFilter(filter) {
    allCards.forEach(function (card, i) {
      var type = card.getAttribute('data-type');
      var show = filter === 'all' || type === filter;
      card.style.display = show ? '' : 'none';
      // Re-trigger entrance animation when revealed after filter
      if (show) {
        card.classList.remove('in-view');
        card.style.setProperty('--i', i);
        setTimeout(function () { card.classList.add('in-view'); }, 50);
      }
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      tabs.forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
      applyFilter(tab.getAttribute('data-filter'));
    });
  });

  // ── Homepage journey viewer ----------------------------------------------
  var journeyLightbox = document.getElementById('achievementLightbox');
  var journeyMedia = document.getElementById('achievementMedia');
  var journeyTitle = document.getElementById('achievementTitle');
  var journeyCaption = document.getElementById('achievementCaption');
  var journeyClose = document.getElementById('achievementClose');

  if (journeyLightbox && journeyMedia) {
    document.querySelectorAll('[data-journey]').forEach(function (card) {
      var photos = JSON.parse(card.getAttribute('data-journey') || '[]');
      var openJourney = function () {
        journeyMedia.innerHTML = '';
        photos.forEach(function (photo) {
          var figure = document.createElement('figure');
          var image = document.createElement('img');
          image.src = photo.src;
          image.alt = photo.caption || card.getAttribute('data-journey-title') || 'Journey photo';
          figure.appendChild(image);
          if (photo.caption) {
            var caption = document.createElement('figcaption');
            caption.textContent = photo.caption;
            figure.appendChild(caption);
          }
          journeyMedia.appendChild(figure);
        });
        journeyTitle.textContent = card.getAttribute('data-journey-title') || '';
        journeyCaption.textContent = card.getAttribute('data-journey-route') || '';
        journeyLightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (journeyClose) journeyClose.focus();
      };
      card.addEventListener('click', openJourney);
      card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openJourney();
        }
      });
    });

    var closeJourney = function () {
      journeyLightbox.classList.remove('open');
      journeyMedia.innerHTML = '';
      document.body.style.overflow = '';
    };
    if (journeyClose) journeyClose.addEventListener('click', closeJourney);
    journeyLightbox.addEventListener('click', function (event) {
      if (event.target === journeyLightbox) closeJourney();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && journeyLightbox.classList.contains('open')) closeJourney();
    });
  }

  // ── Lightbox ──────────────────────────────────────────────────────────────
  var lightbox      = document.getElementById('galleryLightbox');
  var lbMedia       = document.getElementById('lbMedia');
  var lbTitle       = document.getElementById('lbTitle');
  var lbCaption     = document.getElementById('lbCaption');
  var lbClose       = document.getElementById('lbClose');
  var lbPrev        = document.getElementById('lbPrev');
  var lbNext        = document.getElementById('lbNext');

  if (!lightbox) return; // only runs on gallery page

  var lbItems  = []; // built from gallery cards
  var lbIndex  = 0;

  // Collect lightbox-eligible cards (photo cards — not video iframes)
  function buildLbItems() {
    lbItems = [];
    document.querySelectorAll('.gallery-card[data-lb]').forEach(function (card) {
      lbItems.push({
        type    : card.getAttribute('data-type'),
        src     : card.getAttribute('data-lb'),
        title   : card.getAttribute('data-lb-title') || '',
        caption : card.getAttribute('data-lb-caption') || ''
      });
      card.setAttribute('data-lb-idx', lbItems.length - 1);
    });
  }
  buildLbItems();

  function openLightbox(idx) {
    if (!lbItems.length) return;
    lbIndex = ((idx % lbItems.length) + lbItems.length) % lbItems.length;
    var item = lbItems[lbIndex];

    // Clear previous content
    lbMedia.innerHTML = '';

    if (item.type === 'photo') {
      var img = document.createElement('img');
      img.src = item.src;
      img.alt = item.title;
      lbMedia.appendChild(img);
    } else {
      // video — use iframe for YouTube/Vimeo or <video> for direct files
      if (/\.(mp4|webm|ogg)$/i.test(item.src)) {
        var vid = document.createElement('video');
        vid.src = item.src; vid.controls = true; vid.autoplay = false;
        lbMedia.appendChild(vid);
      } else {
        var iframe = document.createElement('iframe');
        iframe.src = item.src + '?autoplay=0';
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'lazy');
        lbMedia.appendChild(iframe);
      }
    }

    lbTitle.textContent   = item.title;
    lbCaption.textContent = item.caption;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (lbClose) lbClose.focus();
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
    // Stop any video/iframe playback
    lbMedia.innerHTML = '';
  }

  // Wire up card clicks
  document.querySelectorAll('.gallery-card[data-lb]').forEach(function (card) {
    card.addEventListener('click', function () {
      openLightbox(parseInt(card.getAttribute('data-lb-idx'), 10));
    });
    card.setAttribute('tabindex', '0');
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openLightbox(parseInt(card.getAttribute('data-lb-idx'), 10));
      }
    });
  });

  if (lbClose) lbClose.addEventListener('click', closeLightbox);
  if (lbPrev)  lbPrev.addEventListener('click', function () { openLightbox(lbIndex - 1); });
  if (lbNext)  lbNext.addEventListener('click', function () { openLightbox(lbIndex + 1); });

  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', function (e) {
    if (!lightbox.classList.contains('open')) return;
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowLeft')  openLightbox(lbIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(lbIndex + 1);
  });

}); // end DOMContentLoaded
