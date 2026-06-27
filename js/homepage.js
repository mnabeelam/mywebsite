/* Homepage — all main page behavior in one file */

(function () {
  'use strict';

  var progressBar = document.getElementById('progressBar');
  var backTop = document.getElementById('backTop');

  window.addEventListener('scroll', function () {
    var scrollTop = document.documentElement.scrollTop;
    var scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;

    if (progressBar && scrollHeight > 0) {
      progressBar.style.width = (scrollTop / scrollHeight) * 100 + '%';
    }
    if (backTop) {
      backTop.style.display = scrollTop > 400 ? 'block' : 'none';
    }
  });

  if (backTop) {
    backTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function animateCounter(el) {
    if (el.dataset.done) return;
    var target = parseInt(el.dataset.target, 10);
    if (!target) return;

    el.dataset.done = '1';
    var value = 0;
    var step = Math.max(1, Math.ceil(target / 80));

    var timer = setInterval(function () {
      value += step;
      if (value >= target) {
        value = target;
        clearInterval(timer);
      }
      el.textContent = value.toLocaleString();
    }, 20);
  }

  var revealObserver = null;

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
          }
        });
      },
      { threshold: 0.3 }
    );

    document.querySelectorAll('.kpi-number, .metric-value').forEach(function (el) {
      observer.observe(el);
    });

    revealObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
          }
        });
      },
      { threshold: 0.12 }
    );

    document.querySelectorAll('.reveal').forEach(function (el) {
      revealObserver.observe(el);
    });

    document.querySelectorAll('.reveal-stagger .reveal-child').forEach(function (el) {
      revealObserver.observe(el);
    });
  } else {
    document.querySelectorAll('.kpi-number, .metric-value').forEach(animateCounter);
  }

  /* Site assistant — knowledge search first; OpenAI only when enabled in config/local.php */
  var AI_ENABLED = false;

  fetch('php/site-config.php', { credentials: 'same-origin' })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      AI_ENABLED = !!(data && data.ai_enabled);
    })
    .catch(function () {
      AI_ENABLED = false;
    });

  var fallbackReplies = {
    oracle: 'Oracle Database 19c Administration and Data Guard experience.',
    vmware: 'VMware ESXi, Hyper-V and Proxmox virtualization.',
    aws: 'AWS Cloud Architecture certified.',
    linux: '18+ years Linux server and infrastructure administration.',
    projects: 'Face Recognition, Oracle Migration, Moodle CBT, DSpace.',
    contact: 'Email: mnabeelam@gmail.com | dd.it@gift.edu.pk | LinkedIn: nabeel-a-1370b4358',
    cybersecurity: 'Cyber Security, infrastructure hardening, and smart campus security.',
    ai: 'AI integration, knowledge platforms, and digital transformation.',
    experience: '18+ years in IT Infrastructure, Cyber Security, Virtualization and Digital Transformation.',
    gift: 'Deputy Director IT at GIFT University, leading enterprise infrastructure and digital transformation.',
    certification: 'Oracle 19c, AWS Cloud Architecture, AWS Cloud Foundations, CCNA Training.',
    shop: 'Browse the IT Gadgets Shop at shop.php for available products, prices, stock, and online ordering.',
    store: 'Ask the assistant: "What products are in the shop?" or visit the IT Shop page to add items to cart.'
  };

  function localFallbackAnswer(question) {
    var q = question.toLowerCase();

    if (q.indexOf('shop') !== -1 || q.indexOf('store') !== -1 || q.indexOf('product') !== -1 || q.indexOf('gadget') !== -1 || q.indexOf('buy') !== -1 || q.indexOf('price') !== -1 || q.indexOf('stock') !== -1 || q.indexOf('lcd') !== -1 || q.indexOf('server') !== -1) {
      return fallbackReplies.shop;
    }
    if (q.indexOf('email') !== -1 || q.indexOf('contact') !== -1 || q.indexOf('reach') !== -1) {
      return fallbackReplies.contact;
    }
    if (q.indexOf('experience') !== -1 || q.indexOf('years') !== -1 || q.indexOf('career') !== -1) {
      return fallbackReplies.experience;
    }
    if (q.indexOf('cert') !== -1) {
      return fallbackReplies.certification;
    }
    if (q.indexOf('gift') !== -1 || q.indexOf('university') !== -1 || q.indexOf('role') !== -1) {
      return fallbackReplies.gift;
    }

    var reply = fallbackReplies[q];
    if (!reply) {
      Object.keys(fallbackReplies).forEach(function (key) {
        if (!reply && q.indexOf(key) !== -1) {
          reply = fallbackReplies[key];
        }
      });
    }

    return reply || 'Try: shop products, oracle, vmware, projects, experience, or contact.';
  }

  function readAssistantJson(response) {
    return response.text().then(function (text) {
      if (!text || !text.trim()) {
        throw new Error('Empty assistant response.');
      }
      try {
        return JSON.parse(text);
      } catch (error) {
        throw new Error('Invalid assistant response.');
      }
    });
  }

  function searchKnowledgeAndShop(question) {
    var body = new FormData();
    body.append('question', question);

    return fetch('php/knowledge-search.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    }).then(function (response) {
      return readAssistantJson(response).then(function (data) {
        return { ok: response.ok, data: data };
      });
    });
  }

  function askOpenAiAssistant(question) {
    var body = new FormData();
    body.append('question', question);

    return fetch('php/chat-api.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' }
    }).then(function (response) {
      return readAssistantJson(response).then(function (data) {
        return { ok: response.ok, data: data };
      });
    });
  }

  window.askAI = function () {
    var input = document.getElementById('ai-input');
    var answer = document.getElementById('ai-answer');
    var button = document.getElementById('ai-submit');
    if (!input || !answer) return;

    var question = input.value.trim();
    if (!question) {
      answer.textContent = 'Try: shop products, oracle, vmware, projects, experience, or contact.';
      return;
    }

    if (button) button.disabled = true;
    answer.textContent = 'Searching career knowledge and IT shop...';

    function finish(text) {
      answer.textContent = text;
      if (button) button.disabled = false;
    }

    searchKnowledgeAndShop(question)
      .then(function (result) {
        if (result.ok && result.data && result.data.answer) {
          finish(result.data.answer);
          return null;
        }
        if (!AI_ENABLED) {
          return 'fallback';
        }
        return askOpenAiAssistant(question);
      })
      .then(function (next) {
        if (next === 'fallback') {
          finish(localFallbackAnswer(question.toLowerCase()));
          return;
        }
        if (!next) {
          return;
        }
        if (next.ok && next.data && next.data.answer) {
          finish(next.data.answer);
          return;
        }
        finish(localFallbackAnswer(question.toLowerCase()));
      })
      .catch(function () {
        finish(localFallbackAnswer(question.toLowerCase()));
      });
  };
  var aiInput = document.getElementById('ai-input');
  var aiSubmit = document.getElementById('ai-submit');

  if (aiSubmit) {
    aiSubmit.addEventListener('click', function () {
      window.askAI();
    });
  }

  if (aiInput) {
    aiInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') window.askAI();
    });
  }

  var aiFab = document.getElementById('aiFab');
  var aiPanel = document.getElementById('aiPanel');

  if (aiFab && aiPanel) {
    aiFab.addEventListener('click', function () {
      var open = aiPanel.hidden;
      aiPanel.hidden = !open;
      aiFab.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open && aiInput) {
        aiInput.focus();
      }
    });
  }

  var navToggle = document.getElementById('navToggle');
  var navLinksEl = document.getElementById('navLinks');

  if (navToggle && navLinksEl) {
    navToggle.addEventListener('click', function () {
      var open = navLinksEl.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    navLinksEl.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        navLinksEl.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  function getNavScrollOffset() {
    var nav = document.querySelector('.site-nav');
    return (nav ? nav.offsetHeight : 64) + 20;
  }

  function scrollToTarget(target) {
    if (!target) return;
    var top = target.getBoundingClientRect().top + window.pageYOffset - getNavScrollOffset();
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      var targetId = link.getAttribute('href');
      if (!targetId || targetId === '#') return;
      var target = document.querySelector(targetId);
      if (!target) return;
      event.preventDefault();
      scrollToTarget(target);
    });
  });

  var navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
  var navSections = [];

  navLinks.forEach(function (link) {
    var section = document.querySelector(link.getAttribute('href'));
    if (section) {
      navSections.push({ link: link, section: section });
    }
  });

  function updateActiveNav() {
    var scrollPos = window.scrollY + getNavScrollOffset();
    var current = navSections[0];

    navSections.forEach(function (item) {
      if (item.section.offsetTop <= scrollPos) {
        current = item;
      }
    });

    navLinks.forEach(function (link) {
      link.classList.remove('is-active');
      link.removeAttribute('aria-current');
    });

    if (current) {
      current.link.classList.add('is-active');
      current.link.setAttribute('aria-current', 'true');
    }
  }

  if (navSections.length) {
    updateActiveNav();
    window.addEventListener('scroll', updateActiveNav, { passive: true });
  }

  function formatCount(value) {
    return Number(value || 0).toLocaleString();
  }

  function updateVisitorDisplay(stats) {
    var totalEl = document.getElementById('visitor-total');
    var todayEl = document.getElementById('visitor-today');
    var viewsEl = document.getElementById('visitor-views');
    if (!stats) return;
    if (totalEl) totalEl.textContent = formatCount(stats.total);
    if (todayEl) todayEl.textContent = formatCount(stats.today);
    if (viewsEl) viewsEl.textContent = formatCount(stats.page_views);
  }

  fetch('php/visitor-track.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'fetch' },
    body: (function () {
      var body = new FormData();
      body.append('page', window.location.pathname || '/index.php');
      return body;
    })()
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data && data.visitors) {
        updateVisitorDisplay(data.visitors);
      }
    })
    .catch(function () {
      fetch('php/visitor-count.php', { credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (data && data.visitors) {
            updateVisitorDisplay(data.visitors);
          }
        })
        .catch(function () {});
    });

  var contactForm = document.getElementById('contactForm');
  var contactService = document.getElementById('contactService');

  if (contactService) {
    var serviceParam = new URLSearchParams(window.location.search).get('service');
    if (serviceParam) {
      var options = Array.prototype.slice.call(contactService.options);
      var matched = options.some(function (option) {
        if (option.value === serviceParam) {
          contactService.value = option.value;
          return true;
        }
        return false;
      });
      if (!matched) {
        contactService.value = 'General Inquiry';
      }
    }
  }

  document.querySelectorAll('.consulting-cta[data-service]').forEach(function (link) {
    link.addEventListener('click', function () {
      if (contactService) {
        contactService.value = link.getAttribute('data-service') || 'General Inquiry';
      }
    });
  });

  if (contactForm) {
    contactForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var feedback = document.getElementById('contactFeedback');
      var submitBtn = document.getElementById('contactSubmit');
      if (feedback) {
        feedback.textContent = 'Sending...';
        feedback.className = 'contact-feedback';
      }
      if (submitBtn) submitBtn.disabled = true;

      var body = new FormData(contactForm);
      fetch('php/contact.php', {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch' }
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (result.ok && result.data.message) {
            if (feedback) {
              feedback.textContent = result.data.message;
              feedback.className = 'contact-feedback is-success';
            }
            contactForm.reset();
            return;
          }
          if (feedback) {
            feedback.textContent = (result.data && result.data.error) || 'Could not send message.';
            feedback.className = 'contact-feedback is-error';
          }
        })
        .catch(function () {
          if (feedback) {
            feedback.textContent = 'Network error. Please try again.';
            feedback.className = 'contact-feedback is-error';
          }
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  var shopGrid = document.getElementById('shopGrid');
  var shopStatus = document.getElementById('shopStatus');
  var shopModal = document.getElementById('shopOrderModal');
  var shopOrderForm = document.getElementById('shopOrderForm');
  var shopCartPanel = document.getElementById('shopCartPanel');
  var shopCartBtn = document.getElementById('shopCartBtn');
  var shopProducts = [];
  var shopCart = [];
  var SHOP_CART_KEY = 'mnabeel_shop_cart';

  function formatShopPrice(product) {
    var amount = Number(product.price || 0).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    });
    return (product.currency || 'PKR') + ' ' + amount;
  }

  function shopImageSrc(path) {
    var fallback = '/assets/shop/placeholder.svg';
    if (!path || typeof path !== 'string') {
      return fallback;
    }
    path = path.trim();
    if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0 || path.indexOf('data:') === 0) {
      return path;
    }
    if (path.charAt(0) === '/') {
      return path;
    }
    return '/' + path.replace(/^\.?\//, '');
  }

  function productCoverImage(product) {
    if (!product) {
      return shopImageSrc('');
    }
    if (product.image) {
      return shopImageSrc(product.image);
    }
    if (Array.isArray(product.images) && product.images.length) {
      return shopImageSrc(product.images[0]);
    }
    return shopImageSrc('');
  }

  function findShopProduct(productId) {
    return shopProducts.find(function (product) {
      return product.id === productId;
    }) || null;
  }

  function loadShopCart() {
    try {
      var saved = JSON.parse(localStorage.getItem(SHOP_CART_KEY) || '[]');
      shopCart = Array.isArray(saved) ? saved : [];
    } catch (error) {
      shopCart = [];
    }
  }

  function saveShopCart() {
    localStorage.setItem(SHOP_CART_KEY, JSON.stringify(shopCart));
    renderShopCart();
  }

  function getCartItemCount() {
    return shopCart.reduce(function (total, item) {
      return total + (item.quantity || 0);
    }, 0);
  }

  function getCartTotal() {
    return shopCart.reduce(function (total, item) {
      return total + (Number(item.price || 0) * Number(item.quantity || 0));
    }, 0);
  }

  function getCartCurrency() {
    return (shopCart[0] && shopCart[0].currency) || 'PKR';
  }

  function addToShopCart(product, quantity) {
    if (!product || !product.id || !product.in_stock) return;
    quantity = Math.max(1, Math.min(99, Number(quantity || 1)));
    var existing = shopCart.find(function (item) {
      return item.id === product.id;
    });
    var maxQty = product.available_stock === null || product.available_stock === undefined
      ? 99
      : Number(product.available_stock || 0);

    if (existing) {
      existing.quantity = Math.min(maxQty, existing.quantity + quantity);
    } else {
      shopCart.push({
        id: product.id,
        name: product.name || 'Product',
        brand: product.brand || '',
        price: Number(product.price || 0),
        currency: product.currency || 'PKR',
        image: productCoverImage(product),
        quantity: Math.min(maxQty, quantity),
        max_stock: maxQty
      });
    }
    saveShopCart();
    openShopCartPanel();
  }

  function removeFromShopCart(productId) {
    shopCart = shopCart.filter(function (item) {
      return item.id !== productId;
    });
    saveShopCart();
  }

  function updateShopCartQuantity(productId, quantity) {
    var item = shopCart.find(function (entry) {
      return entry.id === productId;
    });
    if (!item) return;
    quantity = Math.max(1, Math.min(item.max_stock || 99, Number(quantity || 1)));
    item.quantity = quantity;
    saveShopCart();
  }

  function syncShopCartWithCatalog() {
    shopCart = shopCart.map(function (item) {
      var live = findShopProduct(item.id);
      if (!live || !live.in_stock) {
        return null;
      }
      var maxQty = live.available_stock === null || live.available_stock === undefined
        ? 99
        : Number(live.available_stock || 0);
      return {
        id: item.id,
        name: live.name || item.name,
        brand: live.brand || item.brand,
        price: Number(live.price || item.price || 0),
        currency: live.currency || item.currency || 'PKR',
        image: productCoverImage(live) || shopImageSrc(item.image),
        quantity: Math.min(maxQty, item.quantity || 1),
        max_stock: maxQty
      };
    }).filter(Boolean);
    saveShopCart();
  }

  function openShopCartPanel() {
    if (!shopCartPanel || !shopCartBtn) return;
    shopCartPanel.hidden = false;
    shopCartBtn.setAttribute('aria-expanded', 'true');
  }

  function closeShopCartPanel() {
    if (!shopCartPanel || !shopCartBtn) return;
    shopCartPanel.hidden = true;
    shopCartBtn.setAttribute('aria-expanded', 'false');
  }

  function renderShopCart() {
    var itemsWrap = document.getElementById('shopCartItems');
    var totalEl = document.getElementById('shopCartTotal');
    var badge = document.getElementById('shopCartBadge');
    var checkoutBtn = document.getElementById('shopCartCheckout');
    var count = getCartItemCount();

    if (badge) {
      badge.textContent = String(count);
      badge.hidden = count === 0;
    }
    if (totalEl) {
      totalEl.textContent = getCartCurrency() + ' ' + getCartTotal().toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      });
    }
    if (checkoutBtn) {
      checkoutBtn.disabled = count === 0;
    }
    if (!itemsWrap) return;

    itemsWrap.textContent = '';
    if (!shopCart.length) {
      var empty = document.createElement('p');
      empty.className = 'shop-cart-empty';
      empty.textContent = 'Your cart is empty. Add products from the list below.';
      itemsWrap.appendChild(empty);
      return;
    }

    shopCart.forEach(function (item) {
      var row = document.createElement('div');
      row.className = 'shop-cart-item';

      var thumb = document.createElement('img');
      thumb.className = 'shop-cart-item-image';
      thumb.src = shopImageSrc(item.image);
      thumb.alt = item.name || 'Product';
      thumb.loading = 'lazy';
      thumb.addEventListener('error', function () {
        thumb.src = '/assets/shop/placeholder.svg';
      });

      var meta = document.createElement('div');
      meta.className = 'shop-cart-item-meta';
      meta.innerHTML =
        '<strong>' + (item.name || 'Product') + '</strong>' +
        '<span>' + formatShopPrice(item) + ' each</span>';

      var controls = document.createElement('div');
      controls.className = 'shop-cart-item-controls';

      var qtyInput = document.createElement('input');
      qtyInput.type = 'number';
      qtyInput.min = '1';
      qtyInput.max = String(item.max_stock || 99);
      qtyInput.value = String(item.quantity || 1);
      qtyInput.className = 'shop-cart-qty';
      qtyInput.addEventListener('change', function () {
        updateShopCartQuantity(item.id, qtyInput.value);
      });

      var lineTotal = document.createElement('span');
      lineTotal.className = 'shop-cart-line-total';
      lineTotal.textContent = formatShopPrice({
        price: Number(item.price || 0) * Number(item.quantity || 0),
        currency: item.currency
      });

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'shop-cart-remove';
      removeBtn.textContent = 'Remove';
      removeBtn.addEventListener('click', function () {
        removeFromShopCart(item.id);
      });

      controls.appendChild(qtyInput);
      controls.appendChild(lineTotal);
      controls.appendChild(removeBtn);

      row.appendChild(thumb);
      row.appendChild(meta);
      row.appendChild(controls);
      itemsWrap.appendChild(row);
    });
  }

  function closeShopModal() {
    if (!shopModal) return;
    shopModal.hidden = true;
    shopModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openShopCheckout(mode, product) {
    if (!shopModal || !shopOrderForm) return;

    var summaryEl = document.getElementById('shopModalProduct');
    var singleQtyWrap = document.getElementById('shopSingleQtyWrap');
    var productIdInput = document.getElementById('shopProductId');
    var itemsInput = document.getElementById('shopOrderItems');
    var feedback = document.getElementById('shopOrderFeedback');

    shopOrderForm.reset();
    if (feedback) {
      feedback.textContent = '';
      feedback.className = 'shop-order-feedback';
    }

    if (mode === 'single' && product) {
      if (singleQtyWrap) singleQtyWrap.hidden = false;
      if (productIdInput) productIdInput.value = product.id || '';
      if (itemsInput) itemsInput.value = '';
      if (summaryEl) {
        summaryEl.textContent = '';
        var line = document.createElement('p');
        line.textContent = product.name + ' — ' + formatShopPrice(product);
        summaryEl.appendChild(line);
      }
      document.getElementById('shopProductId').value = product.id || '';
      document.getElementById('shopQuantity').value = '1';
    } else {
      if (singleQtyWrap) singleQtyWrap.hidden = true;
      if (productIdInput) productIdInput.value = '';
      if (itemsInput) {
        itemsInput.value = JSON.stringify(shopCart.map(function (item) {
          return { product_id: item.id, quantity: item.quantity };
        }));
      }
      if (summaryEl) {
        summaryEl.textContent = '';
        shopCart.forEach(function (item) {
          var row = document.createElement('p');
          row.textContent = item.name + ' × ' + item.quantity + ' — ' + formatShopPrice({
            price: Number(item.price || 0) * Number(item.quantity || 0),
            currency: item.currency
          });
          summaryEl.appendChild(row);
        });
        var total = document.createElement('p');
        total.className = 'shop-modal-total';
        total.textContent = 'Cart total: ' + getCartCurrency() + ' ' + getCartTotal().toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 2
        });
        summaryEl.appendChild(total);
      }
    }

    shopModal.hidden = false;
    shopModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    closeShopCartPanel();
    document.getElementById('shopName').focus();
  }

  function renderShopProducts(products) {
    if (!shopGrid) return;
    shopGrid.textContent = '';

    if (!products.length) {
      if (shopStatus) {
        shopStatus.textContent = 'No products listed yet. Check back soon.';
      }
      return;
    }

    if (shopStatus) {
      shopStatus.textContent = products.length + ' product' + (products.length === 1 ? '' : 's') + ' available';
    }

    products.forEach(function (product) {
      var card = document.createElement('article');
      card.className = 'shop-card';

      var images = Array.isArray(product.images) && product.images.length
        ? product.images
        : [productCoverImage(product)];

      var media = document.createElement('div');
      media.className = 'shop-card-media';

      var image = document.createElement('img');
      image.className = 'shop-card-image';
      image.src = shopImageSrc(images[0]);
      image.alt = product.name || 'Product';
      image.loading = 'lazy';
      image.addEventListener('error', function () {
        image.src = '/assets/shop/placeholder.svg';
      });
      media.appendChild(image);

      if (images.length > 1) {
        var thumbs = document.createElement('div');
        thumbs.className = 'shop-card-thumbs';
        images.forEach(function (src, index) {
          var thumb = document.createElement('button');
          thumb.type = 'button';
          thumb.className = 'shop-card-thumb' + (index === 0 ? ' is-active' : '');
          thumb.setAttribute('aria-label', 'Show image ' + (index + 1));
          var thumbImg = document.createElement('img');
          thumbImg.src = shopImageSrc(src);
          thumbImg.alt = '';
          thumbImg.loading = 'lazy';
          thumb.addEventListener('click', function () {
            image.src = shopImageSrc(src);
            thumbs.querySelectorAll('.shop-card-thumb').forEach(function (btn) {
              btn.classList.remove('is-active');
            });
            thumb.classList.add('is-active');
          });
          thumb.appendChild(thumbImg);
          thumbs.appendChild(thumb);
        });
        media.appendChild(thumbs);
      }

      var body = document.createElement('div');
      body.className = 'shop-card-body';

      var category = document.createElement('span');
      category.className = 'shop-card-category';
      category.textContent = [product.brand, product.category].filter(Boolean).join(' · ') || 'IT Gadgets';

      var title = document.createElement('h3');
      title.textContent = product.name || 'Product';

      var price = document.createElement('p');
      price.className = 'shop-card-price';
      price.textContent = formatShopPrice(product);

      var desc = document.createElement('p');
      desc.className = 'shop-card-desc';
      desc.textContent = product.description || '';

      var stock = document.createElement('p');
      stock.className = 'shop-stock' + (product.in_stock ? ' is-available' : '');
      stock.textContent = product.stock_label || (product.in_stock ? 'In stock' : 'Out of stock');

      var actions = document.createElement('div');
      actions.className = 'shop-card-actions';

      var addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'shop-order-btn';
      addBtn.textContent = product.in_stock ? 'Add to Cart' : 'Out of stock';
      addBtn.disabled = !product.in_stock;
      if (product.in_stock) {
        addBtn.addEventListener('click', function () {
          addToShopCart(product, 1);
        });
      }

      var buyBtn = document.createElement('button');
      buyBtn.type = 'button';
      buyBtn.className = 'shop-order-btn secondary';
      buyBtn.textContent = 'Buy Now';
      buyBtn.disabled = !product.in_stock;
      if (product.in_stock) {
        buyBtn.addEventListener('click', function () {
          openShopCheckout('single', product);
        });
      }

      actions.appendChild(addBtn);
      actions.appendChild(buyBtn);

      body.appendChild(category);
      body.appendChild(title);
      body.appendChild(price);
      body.appendChild(desc);
      body.appendChild(stock);
      body.appendChild(actions);
      card.appendChild(media);
      card.appendChild(body);
      shopGrid.appendChild(card);
    });
  }

  loadShopCart();

  if (shopGrid) {
    fetch('php/shop-products.php', { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        shopProducts = (data && data.products) ? data.products : [];
        syncShopCartWithCatalog();
        renderShopProducts(shopProducts);
      })
      .catch(function () {
        if (shopStatus) {
          shopStatus.textContent = 'Could not load products right now.';
        }
      });
  }

  if (shopCartBtn) {
    shopCartBtn.addEventListener('click', function () {
      if (shopCartPanel && shopCartPanel.hidden) {
        openShopCartPanel();
      } else {
        closeShopCartPanel();
      }
    });
  }

  var shopCartClose = document.getElementById('shopCartClose');
  if (shopCartClose) {
    shopCartClose.addEventListener('click', closeShopCartPanel);
  }

  var shopCartCheckout = document.getElementById('shopCartCheckout');
  if (shopCartCheckout) {
    shopCartCheckout.addEventListener('click', function () {
      if (!shopCart.length) return;
      openShopCheckout('cart');
    });
  }

  renderShopCart();

  if (shopModal) {
    shopModal.querySelectorAll('[data-shop-close]').forEach(function (el) {
      el.addEventListener('click', closeShopModal);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !shopModal.hidden) {
        closeShopModal();
      }
    });
  }

  if (shopOrderForm) {
    shopOrderForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var feedback = document.getElementById('shopOrderFeedback');
      var submitBtn = document.getElementById('shopSubmit');
      if (feedback) {
        feedback.textContent = 'Submitting order...';
        feedback.className = 'shop-order-feedback';
      }
      if (submitBtn) submitBtn.disabled = true;

      fetch('php/shop-order.php', {
        method: 'POST',
        body: new FormData(shopOrderForm),
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch' }
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (result.ok && result.data.message) {
            if (feedback) {
              feedback.textContent = result.data.message;
              feedback.className = 'shop-order-feedback is-success';
            }
            shopCart = [];
            saveShopCart();
            shopOrderForm.reset();
            setTimeout(closeShopModal, 2200);
            fetch('php/shop-products.php', { credentials: 'same-origin' })
              .then(function (response) { return response.json(); })
              .then(function (data) {
                shopProducts = (data && data.products) ? data.products : [];
                renderShopProducts(shopProducts);
              })
              .catch(function () {});
            return;
          }
          if (feedback) {
            feedback.textContent = (result.data && result.data.error) || 'Could not place order.';
            feedback.className = 'shop-order-feedback is-error';
          }
        })
        .catch(function () {
          if (feedback) {
            feedback.textContent = 'Network error. Please try again.';
            feedback.className = 'shop-order-feedback is-error';
          }
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  var certGrid = document.getElementById('certGrid');
  var certStatus = document.getElementById('certStatus');

  function renderCertifications(items) {
    if (!certGrid) return;
    certGrid.textContent = '';

    if (!items.length) {
      if (certStatus) {
        certStatus.textContent = 'No certifications listed yet.';
      }
      return;
    }

    if (certStatus) {
      certStatus.textContent = '';
    }

    items.forEach(function (cert, index) {
      var card = document.createElement('article');
      card.className = 'cert-card reveal-child';
      card.style.transitionDelay = (Math.min(index, 7) * 0.08) + 's';

      var title = document.createElement('h3');
      title.textContent = cert.title || 'Certification';
      card.appendChild(title);

      if (cert.knowledge) {
        var knowledgeLabel = document.createElement('p');
        knowledgeLabel.className = 'cert-card-knowledge-label';
        knowledgeLabel.textContent = 'Knowledge';
        card.appendChild(knowledgeLabel);

        var knowledge = document.createElement('p');
        knowledge.className = 'cert-card-meta';
        knowledge.textContent = cert.knowledge;
        card.appendChild(knowledge);
      }

      if (cert.description) {
        var desc = document.createElement('p');
        desc.className = 'cert-card-desc';
        desc.textContent = cert.description;
        card.appendChild(desc);
      }

      certGrid.appendChild(card);
      if ('IntersectionObserver' in window && revealObserver) {
        revealObserver.observe(card);
      }
    });
  }

  if (certGrid) {
    fetch('php/certifications.php', { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        renderCertifications((data && data.certifications) ? data.certifications : []);
      })
      .catch(function () {
        if (certStatus) {
          certStatus.textContent = 'Could not load certifications right now.';
        }
      });
  }

  var profileImg = document.getElementById('heroProfileImg');
  if (profileImg) {
    var profileSources = [
      'assets/profile.png',
      'assets/profile.jpg',
      'assets/profile.jpeg',
      'assets/profile.webp',
      'assets/profile.svg'
    ];
    var profileIndex = 0;

    profileImg.addEventListener('error', function () {
      profileIndex += 1;
      if (profileIndex < profileSources.length) {
        profileImg.src = profileSources[profileIndex];
      }
    });
  }
})();
