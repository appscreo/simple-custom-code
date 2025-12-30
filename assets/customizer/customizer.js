class CssEditor {
  constructor(options = {}) {
    this.options = {
      theme: 'light',
      excludeSelectors: ['#cex-select-btn', '#cex-panel', '.cex-ui'],
      ...options,
    };

    this.rootThemeTarget = document.documentElement;
    this.state = {
      selecting: false,
      hoverTarget: null,
      selectedEl: null,
      currentSelector: '',
      selectorType: 'Current',
      liveStyles: new Map(),   // Map<selector, cssText>
      savedStyles: new Map(),  // Map<selector, cssText>
      activeTab: 'regular',
    };

    const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;

    this._initUI();
    this._initEvents();
    this._applyTheme(isDarkMode ? 'dark' : this.options.theme || 'light');
  }

  /* -------------------- UI -------------------- */

  _initUI() {
    // Selector button
    const btn = document.createElement('button');
    btn.id = 'cex-select-btn';
    btn.className = 'cex-ui';
    btn.type = 'button';
    btn.title = 'Select an element to customize';
    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 128 128" style="enable-background:new 0 0 128 128;" xml:space="preserve"><path class="st0" d="M9.8,9.8C0.5,19.1,0.5,34.1,0.5,64s0,44.9,9.3,54.2s24.3,9.3,54.2,9.3s44.9,0,54.2-9.3s9.3-24.3,9.3-54.2 s0-44.9-9.3-54.2S93.9,0.5,64,0.5S19.1,0.5,9.8,9.8z M73.4,28.7c2.5,0.7,4,3.3,3.4,5.8L60.4,95.9c-0.7,2.5-3.3,4-5.8,3.4 c-2.5-0.7-4-3.3-3.4-5.8l16.4-61.3C68.3,29.6,70.9,28.1,73.4,28.7z M82.9,41.6c1.9-1.9,4.9-1.9,6.7,0l1.3,1.3c4,4,7.4,7.4,9.7,10.4 c2.4,3.2,4.2,6.6,4.2,10.7s-1.7,7.5-4.2,10.7c-2.3,3-5.7,6.4-9.7,10.4l-1.3,1.3c-1.9,1.9-4.9,1.9-6.7,0c-1.9-1.9-1.9-4.9,0-6.7 l1.1-1.1c4.3-4.3,7.2-7.2,9.1-9.7c1.8-2.4,2.2-3.7,2.2-4.9c0-1.2-0.4-2.5-2.2-4.9c-1.9-2.5-4.8-5.4-9.1-9.7l-1.1-1.1 C81,46.5,81,43.4,82.9,41.6z M38.4,41.6c1.9-1.9,4.9-1.9,6.7,0c1.9,1.9,1.9,4.9,0,6.7L44,49.4c-4.3,4.3-7.2,7.2-9.1,9.7 c-1.8,2.4-2.2,3.7-2.2,4.9c0,1.2,0.4,2.5,2.2,4.9c1.9,2.5,4.8,5.4,9.1,9.7l1.1,1.1c1.9,1.9,1.9,4.9,0,6.7c-1.9,1.9-4.9,1.9-6.7,0 l-1.3-1.3c-4-4-7.4-7.4-9.7-10.4c-2.4-3.2-4.2-6.6-4.2-10.7s1.7-7.5,4.2-10.7c2.3-3,5.7-6.4,9.7-10.4L38.4,41.6z"></path></svg>';
    document.body.appendChild(btn);
    this.selectBtn = btn;

    // Floating Save/Clear buttons
    const floatWrap = document.createElement('div');
    floatWrap.id = 'cex-float-controls';
    floatWrap.className = 'cex-ui';
    floatWrap.style.position = 'fixed';
    floatWrap.style.left = '12px';
    floatWrap.style.bottom = '20px';
    floatWrap.style.display = 'none'; // hidden by default
    floatWrap.style.flexDirection = 'column';
    floatWrap.style.gap = '8px';
    floatWrap.style.zIndex = '2147483647';

    const saveBtn = document.createElement('button');
    saveBtn.className = 'cex-btn primary';
    saveBtn.id = 'cex-save';
    saveBtn.innerHTML = '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6C4 4.89543 4.89543 4 6 4H12H14.1716C14.702 4 15.2107 4.21071 15.5858 4.58579L19.4142 8.41421C19.7893 8.78929 20 9.29799 20 9.82843V12V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V6Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 4H13V7C13 7.55228 12.5523 8 12 8H9C8.44772 8 8 7.55228 8 7V4Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 15C7 13.8954 7.89543 13 9 13H15C16.1046 13 17 13.8954 17 15V20H7V15Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><label>Save CSS</label>';

    const clearBtn = document.createElement('button');
    clearBtn.className = 'cex-btn danger';
    clearBtn.id = 'cex-clear';
    clearBtn.innerHTML = '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 9L15 15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 9L9 15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><label>Clear CSS</label>';

    floatWrap.append(saveBtn, clearBtn);
    document.body.appendChild(floatWrap);

    this.saveBtn = saveBtn;
    this.clearBtn = clearBtn;
    this.floatControls = floatWrap;

    // Panel
    const panel = document.createElement('aside');
    panel.id = 'cex-panel';
    panel.className = 'cex-ui';
    panel.innerHTML = `
      <div class="cex-shell">
        <div class="cex-header">
          <div class="cex-headline" style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border);background:var(--surface);">
            <div class="cex-title" style="font-weight:700;letter-spacing:.2px; font-size: 13px;">Customizer</div>
            <div class="cex-actions" style="display:flex;gap:8px;align-items:center;">
              <button class="cex-icon-btn" id="cex-theme-toggle" title="Toggle theme" aria-label="Toggle theme">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                  <path fill="currentColor" d="M12 4a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm0 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM4 12a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1zm13 0a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1zM6.22 6.22a1 1 0 0 1 1.42 0l.7.7a1 1 0 0 1-1.42 1.42l-.7-.7a1 1 0 0 1 0-1.42zm9.44 9.44a1 1 0 0 1 1.42 0l.7.7a1 1 0 0 1-1.42 1.42l-.7-.7a1 1 0 0 1 0-1.42zM6.22 17.78a1 1 0 0 1 0-1.42l.7-.7a1 1 0 1 1 1.42 1.42l-.7.7a1 1 0 0 1-1.42 0zm9.44-9.44a1 1 0 0 1 0-1.42l.7-.7a1 1 0 1 1 1.42 1.42l-.7.7a1 1 0 0 1-1.42 0z"/>
                </svg>
              </button>
              <button class="cex-icon-btn" id="cex-close" title="Close editor" aria-label="Close">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                  <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M6 18L18 6"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="cex-path" style="display:grid;grid-template-columns:1fr 120px;gap:8px;padding:10px 14px;border-bottom:1px solid var(--border);">
            <input id="cex-path-input" type="text" placeholder="CSS selector path" />
            <select id="cex-path-type" title="Selector type">
              <option value="Class">Class based</option>
              <option value="Current">Current</option>
              <option value="ID">ID based</option>
              <option value="Detailed">Detailed</option>
              <option value="MostDetailed">Most Detailed</option>
              <option value="Custom">Custom</option>
            </select>
          </div>
        </div>

        <div class="cex-content"></div>

        <div class="cex-footer">
          <label class="cex-label" for="cex-css-output">Generated CSS</label>
          <textarea id="cex-css-output" spellcheck="false" placeholder="/* Live preview updates immediately; Confirm to save */"></textarea>
          <div class="cex-footer-actions" style="display:flex;gap:8px;">
            <button class="cex-btn primary" id="cex-confirm" style="flex:1;display:flex;align-items:center;gap:6px;justify-content:center;">
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5 13l4 4L19 7"/>
              </svg>
              Apply
            </button>
            <button class="cex-btn" id="cex-reset" style="flex:1;">Reset</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(panel);
    this.panel = panel;

    // Cache controls
    this.pathInput = panel.querySelector('#cex-path-input');
    this.pathType = panel.querySelector('#cex-path-type');
    this.cssOutput = panel.querySelector('#cex-css-output');
    this.closeBtn = panel.querySelector('#cex-close');
    this.themeToggle = panel.querySelector('#cex-theme-toggle');
    this.confirmBtn = panel.querySelector('#cex-confirm');
    this.resetBtn = panel.querySelector('#cex-reset');

    const labelRow = this.panel.querySelector('.cex-footer .cex-label');
    const importantWrap = document.createElement('label');
    importantWrap.style.float = 'right';
    importantWrap.style.fontSize = '12px';
    importantWrap.style.display = 'flex';
    importantWrap.style.alignItems = 'center';
    importantWrap.style.gap = '4px';

    const importantCheckbox = document.createElement('input');
    importantCheckbox.type = 'checkbox';
    importantCheckbox.id = 'cex-important-toggle';

    const importantText = document.createElement('span');
    importantText.textContent = 'Apply !important';

    importantWrap.append(importantCheckbox, importantText);
    labelRow.appendChild(importantWrap);

    this.importantToggle = importantCheckbox;

    // Build content panels
    this._buildFontPanel();
    this._buildSpacingPanel();
    this._buildBackgroundPanel();
    this._buildBorderPanel();
    this._buildBoxShadowPanel();
    this._buildDisplayPanel();
    this._buildTransitionPanel();

  }

  _initEvents() {
    // Toggle selection mode
    this.selectBtn.addEventListener('click', () => this.toggleSelection());

    this.saveBtn.addEventListener('click', () => {
      /*const formatted = this._formatCombinedCssBlocks();
      if (!formatted.trim()) return;
      const blocks = this._splitBlocks(formatted);
      this.state.savedStyles.clear();
      blocks.forEach(({ selector, cssText }) => {
        this.state.savedStyles.set(selector, cssText);
      });
      this._applySavedCss();*/
      const css = this._getSavedCss();
      const styleEl = this._getOrCreateStyleTag('saved');
      //styleEl.textContent = '';
      //this.state.savedStyles.clear();
      saveSCCData(css);
      this._updateSaveClearVisibility();
    });

    this.clearBtn.addEventListener('click', () => {
      this.state.savedStyles.clear();
      const styleEl = this._getOrCreateStyleTag('saved');
      styleEl.textContent = '';
      this._updateSaveClearVisibility();
    });

    this.importantToggle.addEventListener('change', () => {
      this._syncCssFromControls(); // reapply live preview with updated importance
    });

    // Hover outline while selecting
    document.addEventListener('mousemove', (e) => {
      if (!this.state.selecting) return;
      const target = e.target;
      if (this._isExcluded(target)) return this._clearHoverOutline();
      if (!this._isHighlightable(target)) return this._clearHoverOutline();
      if (this.state.hoverTarget !== target) {
        this._clearHoverOutline();
        this.state.hoverTarget = target;
        target.classList.add('cex-outline');
      }
    });

    // Click to select element
    document.addEventListener('click', (e) => {
      if (!this.state.selecting) return;
      const target = e.target;
      if (this._isExcluded(target)) return;
      e.preventDefault();
      e.stopPropagation();
      this._selectElement(target);
    }, true);

    // Close panel (discard live)
    this.closeBtn.addEventListener('click', () => this.closePanelWithoutSaving());

    // Path type change
    this.pathType.addEventListener('change', () => {
      const type = this.pathType.value;
      this.state.selectorType = type;
      if (type === 'Custom') {
        // Ensure we start with a current value (not blank)
        const initial = this._generateSelector(this.state.selectedEl, 'Class');
        this.state.currentSelector = initial;
        this.pathInput.value = initial;
        this._showCustomOverlay(this.state.selectedEl);
      } else {
        this._regenSelectorAndPreview();
      }
    });

    // Path manual edit
    this.pathInput.addEventListener('input', () => {
      this.state.currentSelector = this.pathInput.value.trim();
      this._updatePreviewFromOutput();
      this._syncCssFromControls();
    });

    // CSS textarea live input (preview only)
    this.cssOutput.addEventListener('input', () => {
      this._applyLiveCssFromTextarea();
    });

    // Confirm: save, apply, close
    this.confirmBtn.addEventListener('click', () => {
      const formatted = this._formatCombinedCssBlocks();
      if (!formatted.trim()) return;
      const blocks = this._splitBlocks(formatted);
      blocks.forEach(({ selector, cssText }) => {
        this.state.savedStyles.set(selector, cssText);
      });
      this._applySavedCss();
      this.closePanel();
      this._updateSaveClearVisibility();
    });

    // Reset clears fields and live preview
    this.resetBtn.addEventListener('click', () => this._resetControls());

    // Theme toggle
    this.themeToggle.addEventListener('click', () => {
      const next = this.rootThemeTarget.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      this._applyTheme(next);
    });
  }

  _updateSaveClearVisibility() {
    const hasSaved = this._hasSavedCss();
    this.floatControls.style.display = hasSaved ? 'flex' : 'none';
  }

  /* -------------------- Selection -------------------- */

  toggleSelection() {
    this.state.selecting = !this.state.selecting;
    //this.selectBtn.textContent = this.state.selecting ? 'Cancel selection' : 'Select element';
    if (this.state.selecting) this.selectBtn.classList.add('cex-active-btn');
    else this.selectBtn.classList.remove('cex-active-btn');
    if (!this.state.selecting) this._clearHoverOutline();
  }

  _clearHoverOutline() {
    if (this.state.hoverTarget) {
      this.state.hoverTarget.classList.remove('cex-outline');
      this.state.hoverTarget = null;
    }
  }

  _isExcluded(el) {
    if (!el) return true;
    if (el.closest('#cex-panel')) return true;
    if (el.closest('#cex-select-btn')) return true;
    if (el.closest('#cex-float-controls')) return true;
    if (el.classList && el.classList.contains('cex-ui')) return true;
    return false;
  }

  _isHighlightable(el) {
    if (!(el instanceof Element)) return false;
    const tag = el.tagName.toLowerCase();
    if (tag === 'html' || tag === 'head') return false;
    return true; // include body and all others
  }

  _selectElement(el) {
    this.state.selectedEl = el;
    this.state.selecting = false;
    this._clearHoverOutline();
    //this.selectBtn.textContent = 'Select element';
    this.selectBtn.classList.remove('cex-active-btn');
    this._openPanel();

    // ---- NEW ----
    this._resetControls()                // ← clear UI + collapse panels
    // ---- END NEW ----

    this._setSelectorFromType();
    this._loadSavedCssIntoOutput();
    this._syncCssFromControls();
  }

  /* -------------------- Panel -------------------- */

  _resetAll() {
    // 1. Clear live preview
    this._clearLiveStyles();

    // 2. Reset every control (including the textarea)
    this.panel.querySelectorAll('input, select, textarea').forEach(el => {
      if (el.type === 'checkbox' || el.type === 'radio') {
        el.checked = false;
      } else if (el.tagName === 'SELECT') {
        el.selectedIndex = 0;               // first option (blank)
      } else if (el.type === 'range') {
        el.value = el.defaultValue || '1';
      } else if (el.type === 'color') {
        el.value = '#000000';
      } else {
        el.value = '';
      }
    });

    // 3. Collapse every accordion section
    this.panel.querySelectorAll('.cex-panel').forEach(p => {
      p.classList.remove('open');
      const toggle = p.querySelector('.cex-panel-toggle');
      if (toggle) {
        toggle.textContent = 'Right Arrow';
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    // 4. Make sure the textarea shows nothing (saved CSS will be loaded later)
    this.cssOutput.value = '';
  }

  _openPanel() {
    this.panel.classList.add('active');
    this.selectBtn.style.display = 'none';
  }

  closePanel() {
    this.panel.classList.remove('active');
    this.state.selectedEl = null;
    this.state.currentSelector = '';
    this.cssOutput.value = '';
    this.selectBtn.style.display = 'flex';
    this._clearLiveStyles();
  }

  closePanelWithoutSaving() {
    this._clearLiveStyles();
    this.closePanel();
  }

  /* -------------------- Theme -------------------- */

  _applyTheme(theme) {
    this.rootThemeTarget.setAttribute('data-theme', theme);
  }

  /* -------------------- Selector generation -------------------- */

  _setSelectorFromType() {
    const type = this.pathType.value || 'Class';
    if (type === 'Custom') {
      const initial = this._generateSelector(this.state.selectedEl, 'Class');
      this.state.currentSelector = initial;
      this.pathInput.value = initial;
      this._showCustomOverlay(this.state.selectedEl);
      return;
    }
    const sel = this._generateSelector(this.state.selectedEl, type);
    this.state.selectorType = type;
    this.state.currentSelector = sel;
    this.pathInput.value = sel;
  }

  _generateSelector(el, type) {
    if (!el) return '';

    const current = () => {
      if (el.id) return `#${CSS.escape(el.id)}`;
      if (el.classList.length) return '.' + [...el.classList].map(c => CSS.escape(c)).join('.');
      return el.tagName.toLowerCase();
    };

    const idBased = () => {
      let node = el.parentElement;
      while (node && node !== document.body) {
        if (node.id) return `#${CSS.escape(node.id)} ${current()}`;
        node = node.parentElement;
      }
      // fallback to body
      return `body ${current()}`;
    };

    const classBased = () => {
      let node = el.parentElement;
      while (node && node !== document.body) {
        if (node.classList.length) {
          // Use only the first class
          const firstClass = '.' + CSS.escape(node.classList[0]);
          return `${firstClass} ${current()}`;
        }
        node = node.parentElement;
      }
      // fallback: add body and its first class if exists
      const bodyClass = document.body.classList[0];
      const bodySeg = bodyClass ? `body.${CSS.escape(bodyClass)}` : 'body';
      return `${bodySeg} ${current()}`;
    };

    const classBasedFull = () => {
      let node = el.parentElement;
      while (node && node !== document.body) {
        if (node.classList.length) {
          const parentClasses = '.' + [...node.classList].map(c => CSS.escape(c)).join('.');
          return `${parentClasses} ${current()}`;
        }
        node = node.parentElement;
      }
      // fallback: add body and its first class if exists (exclude using body classes primarily)
      const bodyClass = document.body.classList[0];
      const bodySeg = bodyClass ? `body.${CSS.escape(bodyClass)}` : 'body';
      return `${bodySeg} ${current()}`;
    };

    const detailed = () => {
      let node = el.parentElement;
      while (node && node !== document.body) {
        if (node.id) {
          return `#${CSS.escape(node.id)} ${current()}`;
        }
        if (node.classList.length) {
          const parentClasses = '.' + [...node.classList].map(c => CSS.escape(c)).join('.');
          return `${parentClasses} ${current()}`;
        }
        node = node.parentElement;
      }
      // no tag names, no body tag/classes: only current part
      return current();
    };

    const mostDetailed = () => {
      const base = detailed();
      const bodyClass = document.body.classList[0];
      const bodySeg = bodyClass ? `body.${CSS.escape(bodyClass)}` : 'body';
      return `${bodySeg} ${base}`;
    };

    switch (type) {
      case 'Current': return current();
      case 'ID': return idBased();
      case 'Class': return classBased();
      case 'Detailed': return detailed();
      case 'MostDetailed': return mostDetailed();
      default: return current();
    }
  }

  _regenSelectorAndPreview() {
    if (!this.state.selectedEl) return;
    this._setSelectorFromType();
    this._updatePreviewFromOutput();
    this._syncCssFromControls();
  }

  /* -------------------- Custom overlay -------------------- */

  _showCustomOverlay(el) {
    if (!el) return;
    const overlay = document.createElement('div');
    overlay.className = 'cex-ui';
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.background = 'rgba(0,0,0,0.35)';
    overlay.style.zIndex = '2147483646';
    overlay.style.display = 'grid';
    overlay.style.placeItems = 'center';

    const card = document.createElement('div');
    card.className = 'cex-ui-pathpicker';
    card.style.width = '480px';
    card.style.maxWidth = '90vw';
    card.style.maxHeight = '80vh';
    card.style.overflow = 'auto';
    card.style.background = 'var(--panel-bg)';
    card.style.color = 'var(--panel-fg)';
    card.style.border = '1px solid var(--border)';
    card.style.borderRadius = '12px';
    card.style.boxShadow = 'var(--shadow)';
    card.style.display = 'grid';
    card.style.gridTemplateRows = 'auto 1fr auto';

    const header = document.createElement('div');
    header.className = 'cex-ui-pathpicker-header';
    header.style.display = 'flex';
    header.style.alignItems = 'center';
    header.style.justifyContent = 'space-between';
    header.style.padding = '10px 12px';
    header.style.borderBottom = '1px solid var(--border)';
    const htitle = document.createElement('div');
    htitle.textContent = 'Build custom selector';
    htitle.style.fontWeight = '700';
    const hclose = document.createElement('button');
    hclose.className = 'cex-icon-btn';
    hclose.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M6 18L18 6"/></svg>`;
    header.append(htitle, hclose);

    const body = document.createElement('div');
    body.className = 'cex-ui-pathpicker-body';
    body.style.padding = '10px 12px';
    body.style.display = 'grid';
    body.style.gap = '8px';

    const footer = document.createElement('div');
    footer.className = 'cex-ui-pathpicker-footer';
    footer.style.display = 'flex';
    footer.style.gap = '8px';
    footer.style.padding = '10px 12px';
    footer.style.borderTop = '1px solid var(--border)';
    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'cex-btn primary';
    confirmBtn.style.flex = '1';
    confirmBtn.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>Confirm`;
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'cex-btn';
    cancelBtn.style.flex = '1';
    cancelBtn.textContent = 'Cancel';
    footer.append(confirmBtn, cancelBtn);

    card.append(header, body, footer);
    overlay.appendChild(card);
    document.body.appendChild(overlay);

    // Build ancestor list (vertical), each with tag/id/classes and checkboxes
    const rows = [];
    let node = el;
    while (node && node !== document.documentElement) {
      const row = document.createElement('div');
      row.style.border = '1px solid var(--border)';
      row.style.borderRadius = '8px';
      row.style.padding = '8px';
      row.style.display = 'grid';
      row.style.gap = '6px';
      const title = document.createElement('div');
      title.textContent = `Element: ${node.tagName.toLowerCase()}`;
      title.style.fontWeight = '600';
      row.appendChild(title);

      // tag checkbox
      const tagWrap = document.createElement('label');
      tagWrap.style.display = 'flex';
      tagWrap.style.gap = '8px';
      const tagCheck = document.createElement('input');
      tagCheck.type = 'checkbox';
      tagCheck.checked = true;
      const tagText = document.createElement('span');
      tagText.textContent = node.tagName.toLowerCase();
      tagText.dataset.kind = 'tag';
      tagWrap.append(tagCheck, tagText);
      row.appendChild(tagWrap);

      // id checkbox
      if (node.id) {
        const idWrap = document.createElement('label');
        idWrap.style.display = 'flex';
        idWrap.style.gap = '8px';
        const idCheck = document.createElement('input');
        idCheck.type = 'checkbox';
        idCheck.checked = true;
        const idText = document.createElement('span');
        idText.textContent = `#${node.id}`;
        idText.dataset.kind = 'id';
        idWrap.append(idCheck, idText);
        row.appendChild(idWrap);
      }

      // classes checkboxes
      if (node.classList.length) {
        const clsTitle = document.createElement('div');
        clsTitle.textContent = 'Classes:';
        row.appendChild(clsTitle);
        [...node.classList].forEach(cls => {
          const clsWrap = document.createElement('label');
          clsWrap.style.display = 'flex';
          clsWrap.style.gap = '8px';
          const clsCheck = document.createElement('input');
          clsCheck.type = 'checkbox';
          clsCheck.checked = true;
          const clsText = document.createElement('span');
          clsText.textContent = `.${cls}`;
          clsText.dataset.kind = 'class';
          clsWrap.append(clsCheck, clsText);
          row.appendChild(clsWrap);
        });
      }

      body.appendChild(row);
      rows.push({ row, node });
      node = node.parentElement;
    }

    const closeOverlay = () => overlay.remove();
    hclose.addEventListener('click', closeOverlay);
    cancelBtn.addEventListener('click', closeOverlay);

    confirmBtn.addEventListener('click', () => {
      const parts = [];
      rows.forEach(({ row }) => {
        // build segment for this row based on checked items
        const segs = [];
        row.querySelectorAll('label').forEach(lbl => {
          const box = lbl.querySelector('input[type="checkbox"]');
          const span = lbl.querySelector('span');
          if (!box || !span) return;
          if (!box.checked) return;
          const txt = span.textContent;
          const kind = span.dataset.kind;
          if (kind === 'id' || kind === 'class') {
            segs.push(txt);
          } else if (kind === 'tag') {
            segs.push(txt); // include tag if checked
          }
        });
        if (segs.length) parts.unshift(segs.join(''));
      });

      const selector = parts.join(' ');
      if (selector.trim().length) {
        this.state.currentSelector = selector;
        this.pathInput.value = selector;
        this._syncCssFromControls();
      }
      closeOverlay();
      this._updateSaveClearVisibility();
    });
  }

  /* -------------------- CSS application -------------------- */

  _styleTagId(kind = 'live') {
    return kind === 'saved' ? 'cex-saved-styles' : 'cex-live-styles';
  }

  _getOrCreateStyleTag(kind = 'live') {
    const id = this._styleTagId(kind);
    let style = document.getElementById(id);
    if (!style) {
      style = document.createElement('style');
      style.id = id;
      style.type = 'text/css';
      document.head.appendChild(style);
    }
    return style;
  }

  _composeCss(map) {
    let out = '';
    for (const [selector, cssText] of map.entries()) {
      const blockText = (cssText || '').trim();
      if (!selector || !blockText) continue;
      out += `${selector} {\n${this._indentLines(blockText, 2)}\n}\n\n`;
    }
    return out;
  }

  _indentLines(text, spaces = 2) {
    const pad = ' '.repeat(spaces);
    return text.split('\n').map(l => (l.trim() ? pad + l : l)).join('\n');
  }

  _applyLiveCssFromTextarea() {
    const raw = this.cssOutput.value || '';
    const blocks = this._splitBlocks(raw);
    this.state.liveStyles.clear();
    blocks.forEach(({ selector, cssText }) => {
      if (selector && cssText.trim()) this.state.liveStyles.set(selector, cssText.trim());
    });
    const styleEl = this._getOrCreateStyleTag('live');
    styleEl.textContent = this._composeCss(this.state.liveStyles);
  }

  _applyLiveCss(selector, cssText) {
    if (!selector) return;
    if (cssText && cssText.trim().length) {
      this.state.liveStyles.set(selector, cssText.trim());
    } else {
      this.state.liveStyles.delete(selector);
    }
    const styleEl = this._getOrCreateStyleTag('live');
    styleEl.textContent = this._composeCss(this.state.liveStyles);
  }

  _applySavedCss() {
    const styleEl = this._getOrCreateStyleTag('saved');
    styleEl.textContent = this._composeCss(this.state.savedStyles);
  }

  _hasSavedCss() {
    const styleEl = this._getOrCreateStyleTag('saved');
    return styleEl.textContent != '';
  }

  _getSavedCss() {
    const styleEl = this._getOrCreateStyleTag('saved');
    return styleEl.textContent;;
  }

  _clearSavedCss() {
    const styleEl = this._getOrCreateStyleTag('saved');
    styleEl.textContent = '';
  }

  _clearLiveStyles() {
    this.state.liveStyles.clear();
    const styleEl = this._getOrCreateStyleTag('live');
    styleEl.textContent = '';
  }

  /* -------------------- Blocks parsing/formatting -------------------- */

  _splitBlocks(text) {
    const out = [];
    const regex = /([^{]+)\{([^}]*)\}/gms;
    let m;
    while ((m = regex.exec(text)) !== null) {
      const selector = m[1].trim();
      const cssText = (m[2] || '').trim();
      if (selector) out.push({ selector, cssText });
    }
    return out;
  }

  _formatCombinedCssBlocks() {
    const baseSelector = (this.state.currentSelector || '').trim();
    if (!baseSelector) return '';

    const regularVals = this._collectPanelValues('regular');
    const hoverVals = this._collectPanelValues('hover');

    const regularCss = this._cssFromValues(regularVals);
    const hoverCss = this._cssFromValues(hoverVals);

    const transitionVals = this._collectPanelValues('transition');
    const transitionCss = this._cssFromValues(transitionVals);

    let out = '';
    if (regularCss.trim() || transitionCss.trim()) {
      out += `${baseSelector} {\n${this._indentLines((regularCss + '\n' + transitionCss).trim(), 2)}\n}\n\n`;
    }
    if (hoverCss.trim()) {
      out += `${baseSelector}:hover {\n${this._indentLines(hoverCss.trim(), 2)}\n}\n\n`;
    }
    return out.trimEnd();
  }

  _updatePreviewFromOutput() {
    this._applyLiveCssFromTextarea();
  }

  _loadSavedCssIntoOutput() {
    const baseSel = this.state.currentSelector;
    const base = this.state.savedStyles.get(baseSel) || '';
    const hover = this.state.savedStyles.get(`${baseSel}:hover`) || '';
    let combined = '';
    if (base.trim()) {
      combined += `${baseSel} {\n${this._indentLines(base.trim(), 2)}\n}\n\n`;
    }
    if (hover.trim()) {
      combined += `${baseSel}:hover {\n${this._indentLines(hover.trim(), 2)}\n}\n\n`;
    }
    this.cssOutput.value = combined.trimEnd();
  }

  /* -------------------- Controls: Font panel -------------------- */

  _buildFontPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
      <div class="cex-panel-header">
        <div class="cex-panel-title">Font</div>
        <button class="cex-panel-toggle" type="button" aria-expanded="true">▾</button>
      </div>
      <div class="cex-panel-body">
        <div class="cex-tabs">
          <div class="cex-tablist">
            <button class="cex-tab active" data-tab="regular">Regular</button>
            <button class="cex-tab" data-tab="hover">Hover</button>
          </div>
          <div class="cex-tabpanel active" data-panel="regular"></div>
          <div class="cex-tabpanel" data-panel="hover"></div>
        </div>
      </div>
    `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });
    // after setting up the toggle listener
    // remove: wrap.classList.add('open');
    toggle.textContent = '▸';
    toggle.setAttribute('aria-expanded', 'false');

    // Tabs switching
    const tablist = wrap.querySelector('.cex-tablist');
    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('.cex-tab');
      if (!btn) return;
      wrap.querySelectorAll('.cex-tab').forEach(t => t.classList.remove('active'));
      wrap.querySelectorAll('.cex-tabpanel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      wrap.querySelector(`.cex-tabpanel[data-panel="${key}"]`).classList.add('active');
      this.state.activeTab = key;
      this._syncCssFromControls();
    });

    // Fields
    const fields = [
      { key: 'font-family', label: 'Font family', type: 'text' },
      { key: 'font-size', label: 'Font size', type: 'unit' },
      { key: 'line-height', label: 'Line height', type: 'unit' },
      { key: 'letter-spacing', label: 'Letter spacing', type: 'unit' },
      { key: 'font-weight', label: 'Font weight', type: 'select', options: ['', '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold', 'bolder', 'lighter'] },
      { key: 'font-style', label: 'Font style', type: 'select', options: ['', 'normal', 'italic', 'oblique', 'initial', 'inherit'] },
      { key: 'text-transform', label: 'Text transform', type: 'select', options: ['', 'none', 'capitalize', 'uppercase', 'lowercase', 'full-width', 'initial', 'inherit'] },
      { key: 'color', label: 'Color', type: 'color' },
    ];

    const regularPanel = wrap.querySelector('[data-panel="regular"]');
    const hoverPanel = wrap.querySelector('[data-panel="hover"]');

    const buildField = (host, f, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope; // 'regular' | 'hover'
      row.dataset.prop = f.key;

      const label = document.createElement('div');
      label.className = 'cex-label';
      label.textContent = f.label;
      label.style.fontSize = '13px';

      let control;
      switch (f.type) {
        case 'unit': {
          control = document.createElement('div');
          control.className = 'cex-unit';
          const input = document.createElement('input');
          input.type = 'text';
          input.placeholder = 'Value';
          input.dataset.role = 'value';
          input.style.fontSize = '12px';

          const select = document.createElement('select');
          select.dataset.role = 'unit';
          ['px', '%', 'em', 'rem', 'vh', 'vw'].forEach(u => {
            const opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u || '—';
            select.appendChild(opt);
          });
          select.style.fontSize = '12px';

          control.appendChild(input);
          control.appendChild(select);

          input.addEventListener('input', () => this._onFieldChange(row));
          select.addEventListener('change', () => this._onFieldChange(row));
          break;
        }
        case 'select': {
          control = document.createElement('select');
          f.options.forEach(val => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val || '—';
            control.appendChild(opt);
          });
          control.style.fontSize = '12px';
          control.addEventListener('change', () => this._onFieldChange(row));
          break;
        }
        case 'color': {
          control = this._buildPopupColorField(row);
          break;
        }
        default: {
          control = document.createElement('input');
          control.type = 'text';
          control.placeholder = 'Value';
          control.style.fontSize = '12px';
          control.addEventListener('input', () => this._onFieldChange(row));
        }
      }

      row.appendChild(label);
      row.appendChild(control);
      host.appendChild(row);
    };

    fields.forEach(f => {
      buildField(regularPanel, f, 'regular');
      buildField(hoverPanel, f, 'hover');
    });
  }

  _buildBackgroundPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Background</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="true">▾</button>
    </div>
    <div class="cex-panel-body">
      <div class="cex-tabs">
        <div class="cex-tablist">
          <button class="cex-tab active" data-tab="regular">Regular</button>
          <button class="cex-tab" data-tab="hover">Hover</button>
        </div>
        <div class="cex-tabpanel active" data-panel="regular"></div>
        <div class="cex-tabpanel" data-panel="hover"></div>
      </div>
    </div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });
    // after setting up the toggle listener
    // remove: wrap.classList.add('open');
    toggle.textContent = '▸';
    toggle.setAttribute('aria-expanded', 'false');

    // Tabs switching
    const tablist = wrap.querySelector('.cex-tablist');
    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('.cex-tab');
      if (!btn) return;
      wrap.querySelectorAll('.cex-tab').forEach(t => t.classList.remove('active'));
      wrap.querySelectorAll('.cex-tabpanel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      wrap.querySelector(`.cex-tabpanel[data-panel="${key}"]`).classList.add('active');
      this.state.activeTab = key;
      this._syncCssFromControls();
    });

    const fields = [
      { key: 'background-color', label: 'Background color', type: 'color' },
      { key: 'background', label: 'Background', type: 'text' },
      { key: 'background-image', label: 'Background image', type: 'text' },
      // Conditional fields (hidden initially)
      {
        key: 'background-attachment', label: 'Attachment', type: 'select',
        options: ['', 'scroll', 'fixed', 'local'], conditional: 'background-image'
      },
      {
        key: 'background-clip', label: 'Clip', type: 'select',
        options: ['', 'border-box', 'padding-box', 'content-box', 'text'], conditional: 'background-image'
      },
      {
        key: 'background-origin', label: 'Origin', type: 'select',
        options: ['', 'border-box', 'padding-box', 'content-box'], conditional: 'background-image'
      },
      { key: 'background-position', label: 'Position', type: 'text', conditional: 'background-image' },
      {
        key: 'background-repeat', label: 'Repeat', type: 'select',
        options: ['', 'repeat', 'repeat-x', 'repeat-y', 'no-repeat', 'space', 'round'], conditional: 'background-image'
      },
      {
        key: 'background-size', label: 'Size', type: 'select',
        options: ['', 'auto', 'cover', 'contain'], conditional: 'background-image'
      },
    ];

    const regularPanel = wrap.querySelector('[data-panel="regular"]');
    const hoverPanel = wrap.querySelector('[data-panel="hover"]');

    const buildField = (host, f, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope;
      row.dataset.prop = f.key;
      if (f.conditional) row.dataset.conditional = f.conditional;
      const label = document.createElement('div');
      label.className = 'cex-label';
      label.textContent = f.label;
      label.style.fontSize = '13px';

      let control;
      if (f.type === 'color') {
        control = this._buildPopupColorField(row);
      } else if (f.type === 'select') {
        control = document.createElement('select');
        f.options.forEach(val => {
          const opt = document.createElement('option');
          opt.value = val;
          opt.textContent = val || '—';
          control.appendChild(opt);
        });
        control.style.fontSize = '12px';
        control.addEventListener('change', () => this._onFieldChange(row));
      } else {
        control = document.createElement('input');
        control.type = 'text';
        control.placeholder = 'Value';
        control.style.fontSize = '12px';
        control.addEventListener('input', () => {
          if (f.key === 'background-image') {
            let v = control.value.trim();
            if (v && !/^url\(/.test(v)) {
              v = `url("${v}")`;
              control.value = v;
            }
          }
          this._onFieldChange(row);
        });
      }

      row.appendChild(label);
      row.appendChild(control);
      if (f.conditional) row.style.display = 'none'; // hidden initially
      host.appendChild(row);
    };

    fields.forEach(f => {
      buildField(regularPanel, f, 'regular');
      buildField(hoverPanel, f, 'hover');
    });
  }

  _buildBorderPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Border</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="false">▸</button>
    </div>
    <div class="cex-panel-body">
      <div class="cex-tabs">
        <div class="cex-tablist">
          <button class="cex-tab active" data-tab="regular">Regular</button>
          <button class="cex-tab" data-tab="hover">Hover</button>
        </div>
        <div class="cex-tabpanel active" data-panel="regular"></div>
        <div class="cex-tabpanel" data-panel="hover"></div>
      </div>
    </div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });

    // Tabs switching
    const tablist = wrap.querySelector('.cex-tablist');
    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('.cex-tab');
      if (!btn) return;
      wrap.querySelectorAll('.cex-tab').forEach(t => t.classList.remove('active'));
      wrap.querySelectorAll('.cex-tabpanel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      wrap.querySelector(`.cex-tabpanel[data-panel="${key}"]`).classList.add('active');
      this.state.activeTab = key;
      this._syncCssFromControls();
    });

    const regularPanel = wrap.querySelector('[data-panel="regular"]');
    const hoverPanel = wrap.querySelector('[data-panel="hover"]');

    // Helper to build a unit field
    const buildUnitField = (host, key, label, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope;
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('div');
      control.className = 'cex-unit';
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'Value';
      input.dataset.role = 'value';
      input.style.fontSize = '12px';
      const select = document.createElement('select');
      select.dataset.role = 'unit';
      ['px', 'em', 'rem', '%'].forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        select.appendChild(opt);
      });
      select.style.fontSize = '12px';
      control.append(input, select);

      input.addEventListener('input', () => this._onFieldChange(row));
      select.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, control);
      host.appendChild(row);
      return { row, input, select };
    };

    // Helper to build color and style
    const buildSimpleField = (host, f, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope;
      row.dataset.prop = f.key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = f.label;
      lbl.style.fontSize = '13px';

      let control;
      if (f.type === 'color') {
        control = this._buildPopupColorField(row);
      } else if (f.type === 'select') {
        control = document.createElement('select');
        f.options.forEach(val => {
          const opt = document.createElement('option');
          opt.value = val;
          opt.textContent = val;
          control.appendChild(opt);
        });
        control.style.fontSize = '12px';
        control.addEventListener('change', () => this._onFieldChange(row));
      }
      row.append(lbl, control);
      host.appendChild(row);
    };

    // Build both regular and hover
    const buildForScope = (panel, scope) => {
      // Border section heading
      const borderHeading = document.createElement('h4');
      borderHeading.textContent = 'Border';
      borderHeading.style.margin = '10px 0 6px';
      panel.appendChild(borderHeading);


      // Link toggle for widths
      const linkBtn = document.createElement('button');
      linkBtn.type = 'button';
      linkBtn.classList.add('cex-link-button');
      linkBtn.classList.add('cex-active');
      linkBtn.innerHTML = `<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22ZM9.198 7.25H9.30203C10.2005 7.24997 10.9497 7.24995 11.5445 7.32991C12.1723 7.41432 12.7391 7.59999 13.1945 8.05546C13.65 8.51093 13.8357 9.07773 13.9201 9.70552C14.0001 10.3003 14 11.0495 14 11.948L14 12C14 12.4142 13.6642 12.75 13.25 12.75C12.8358 12.75 12.5 12.4142 12.5 12C12.5 11.036 12.4984 10.3884 12.4335 9.9054C12.3714 9.44393 12.2642 9.24644 12.1339 9.11612C12.0036 8.9858 11.8061 8.87858 11.3446 8.81654C10.8616 8.7516 10.214 8.75 9.25 8.75C8.28599 8.75 7.63843 8.7516 7.15539 8.81654C6.69393 8.87858 6.49644 8.9858 6.36612 9.11612C6.2358 9.24644 6.12858 9.44393 6.06654 9.9054C6.0016 10.3884 6 11.036 6 12C6 12.964 6.0016 13.6116 6.06654 14.0946C6.12858 14.5561 6.2358 14.7536 6.36612 14.8839C6.49644 15.0142 6.69393 15.1214 7.15539 15.1835C7.63843 15.2484 8.28599 15.25 9.25 15.25C9.66422 15.25 10 15.5858 10 16C10 16.4142 9.66422 16.75 9.25 16.75L9.19798 16.75C8.29951 16.75 7.5503 16.7501 6.95552 16.6701C6.32773 16.5857 5.76093 16.4 5.30546 15.9445C4.84999 15.4891 4.66432 14.9223 4.57991 14.2945C4.49995 13.6997 4.49997 12.9505 4.5 12.052V11.948C4.49997 11.0495 4.49995 10.3003 4.57991 9.70552C4.66432 9.07773 4.84999 8.51093 5.30546 8.05546C5.76093 7.59999 6.32773 7.41432 6.95552 7.32991C7.55029 7.24995 8.29954 7.24997 9.198 7.25ZM16.8446 8.81654C16.3616 8.7516 15.714 8.75 14.75 8.75C14.3358 8.75 14 8.41422 14 8C14 7.58579 14.3358 7.25 14.75 7.25L14.802 7.25C15.7005 7.24997 16.4497 7.24995 17.0445 7.32991C17.6723 7.41432 18.2391 7.59999 18.6945 8.05546C19.15 8.51093 19.3357 9.07773 19.4201 9.70552C19.5001 10.3003 19.5 11.0495 19.5 11.9479V12.052C19.5 12.9505 19.5001 13.6997 19.4201 14.2945C19.3357 14.9223 19.15 15.4891 18.6945 15.9445C18.2391 16.4 17.6723 16.5857 17.0445 16.6701C16.4497 16.7501 15.7005 16.75 14.802 16.75H14.698C13.7995 16.75 13.0503 16.7501 12.4555 16.6701C11.8277 16.5857 11.2609 16.4 10.8055 15.9445C10.35 15.4891 10.1643 14.9223 10.0799 14.2945C9.99995 13.6997 9.99997 12.9505 10 12.052L10 12C10 11.5858 10.3358 11.25 10.75 11.25C11.1642 11.25 11.5 11.5858 11.5 12C11.5 12.964 11.5016 13.6116 11.5665 14.0946C11.6286 14.5561 11.7358 14.7536 11.8661 14.8839C11.9964 15.0142 12.1939 15.1214 12.6554 15.1835C13.1384 15.2484 13.786 15.25 14.75 15.25C15.714 15.25 16.3616 15.2484 16.8446 15.1835C17.3061 15.1214 17.5036 15.0142 17.6339 14.8839C17.7642 14.7536 17.8714 14.5561 17.9335 14.0946C17.9984 13.6116 18 12.964 18 12C18 11.036 17.9984 10.3884 17.9335 9.9054C17.8714 9.44393 17.7642 9.24644 17.6339 9.11612C17.5036 8.9858 17.3061 8.87858 16.8446 8.81654Z"/></svg>`;
      linkBtn.style.fontSize = '11px';
      linkBtn.style.margin = '4px 0';
      panel.appendChild(linkBtn);

      const widthFields = {
        top: buildUnitField(panel, 'border-top-width', 'Top width', scope),
        right: buildUnitField(panel, 'border-right-width', 'Right width', scope),
        bottom: buildUnitField(panel, 'border-bottom-width', 'Bottom width', scope),
        left: buildUnitField(panel, 'border-left-width', 'Left width', scope),
      };

      let linked = true;
      const syncWidths = (src) => {
        if (!linked) return;
        const val = src.input.value;
        const unit = src.select.value;
        Object.values(widthFields).forEach(f => {
          f.input.value = val;
          f.select.value = unit;
        });
        this._syncCssFromControls();
      };
      Object.values(widthFields).forEach(f => {
        f.input.addEventListener('input', () => syncWidths(f));
        f.select.addEventListener('change', () => syncWidths(f));
      });
      linkBtn.addEventListener('click', () => {
        linked = !linked;
        //linkBtn.textContent = linked ? '🔗 Link widths' : '🔓 Unlinked widths';
        if (linked) linkBtn.classList.add('cex-active');
        else linkBtn.classList.remove('cex-active');
      });

      buildSimpleField(panel, { key: 'border-color', label: 'Border color', type: 'color' }, scope);
      buildSimpleField(panel, {
        key: 'border-style', label: 'Border style', type: 'select',
        options: ['', 'none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset']
      }, scope);


      // Border radius section heading
      const radiusHeading = document.createElement('h4');
      radiusHeading.textContent = 'Border Radius';
      radiusHeading.style.margin = '12px 0 6px';
      panel.appendChild(radiusHeading);

      // Link toggle for radii
      const linkRadiusBtn = document.createElement('button');
      linkRadiusBtn.type = 'button';
      linkRadiusBtn.classList.add('cex-link-button');
      linkRadiusBtn.classList.add('cex-active');
      linkRadiusBtn.innerHTML = `<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22ZM9.198 7.25H9.30203C10.2005 7.24997 10.9497 7.24995 11.5445 7.32991C12.1723 7.41432 12.7391 7.59999 13.1945 8.05546C13.65 8.51093 13.8357 9.07773 13.9201 9.70552C14.0001 10.3003 14 11.0495 14 11.948L14 12C14 12.4142 13.6642 12.75 13.25 12.75C12.8358 12.75 12.5 12.4142 12.5 12C12.5 11.036 12.4984 10.3884 12.4335 9.9054C12.3714 9.44393 12.2642 9.24644 12.1339 9.11612C12.0036 8.9858 11.8061 8.87858 11.3446 8.81654C10.8616 8.7516 10.214 8.75 9.25 8.75C8.28599 8.75 7.63843 8.7516 7.15539 8.81654C6.69393 8.87858 6.49644 8.9858 6.36612 9.11612C6.2358 9.24644 6.12858 9.44393 6.06654 9.9054C6.0016 10.3884 6 11.036 6 12C6 12.964 6.0016 13.6116 6.06654 14.0946C6.12858 14.5561 6.2358 14.7536 6.36612 14.8839C6.49644 15.0142 6.69393 15.1214 7.15539 15.1835C7.63843 15.2484 8.28599 15.25 9.25 15.25C9.66422 15.25 10 15.5858 10 16C10 16.4142 9.66422 16.75 9.25 16.75L9.19798 16.75C8.29951 16.75 7.5503 16.7501 6.95552 16.6701C6.32773 16.5857 5.76093 16.4 5.30546 15.9445C4.84999 15.4891 4.66432 14.9223 4.57991 14.2945C4.49995 13.6997 4.49997 12.9505 4.5 12.052V11.948C4.49997 11.0495 4.49995 10.3003 4.57991 9.70552C4.66432 9.07773 4.84999 8.51093 5.30546 8.05546C5.76093 7.59999 6.32773 7.41432 6.95552 7.32991C7.55029 7.24995 8.29954 7.24997 9.198 7.25ZM16.8446 8.81654C16.3616 8.7516 15.714 8.75 14.75 8.75C14.3358 8.75 14 8.41422 14 8C14 7.58579 14.3358 7.25 14.75 7.25L14.802 7.25C15.7005 7.24997 16.4497 7.24995 17.0445 7.32991C17.6723 7.41432 18.2391 7.59999 18.6945 8.05546C19.15 8.51093 19.3357 9.07773 19.4201 9.70552C19.5001 10.3003 19.5 11.0495 19.5 11.9479V12.052C19.5 12.9505 19.5001 13.6997 19.4201 14.2945C19.3357 14.9223 19.15 15.4891 18.6945 15.9445C18.2391 16.4 17.6723 16.5857 17.0445 16.6701C16.4497 16.7501 15.7005 16.75 14.802 16.75H14.698C13.7995 16.75 13.0503 16.7501 12.4555 16.6701C11.8277 16.5857 11.2609 16.4 10.8055 15.9445C10.35 15.4891 10.1643 14.9223 10.0799 14.2945C9.99995 13.6997 9.99997 12.9505 10 12.052L10 12C10 11.5858 10.3358 11.25 10.75 11.25C11.1642 11.25 11.5 11.5858 11.5 12C11.5 12.964 11.5016 13.6116 11.5665 14.0946C11.6286 14.5561 11.7358 14.7536 11.8661 14.8839C11.9964 15.0142 12.1939 15.1214 12.6554 15.1835C13.1384 15.2484 13.786 15.25 14.75 15.25C15.714 15.25 16.3616 15.2484 16.8446 15.1835C17.3061 15.1214 17.5036 15.0142 17.6339 14.8839C17.7642 14.7536 17.8714 14.5561 17.9335 14.0946C17.9984 13.6116 18 12.964 18 12C18 11.036 17.9984 10.3884 17.9335 9.9054C17.8714 9.44393 17.7642 9.24644 17.6339 9.11612C17.5036 8.9858 17.3061 8.87858 16.8446 8.81654Z"/></svg>`;
      linkRadiusBtn.style.fontSize = '11px';
      linkRadiusBtn.style.margin = '4px 0';
      panel.appendChild(linkRadiusBtn);

      const radiusFields = {
        tl: buildUnitField(panel, 'border-top-left-radius', 'Top-left radius', scope),
        tr: buildUnitField(panel, 'border-top-right-radius', 'Top-right radius', scope),
        br: buildUnitField(panel, 'border-bottom-right-radius', 'Bottom-right radius', scope),
        bl: buildUnitField(panel, 'border-bottom-left-radius', 'Bottom-left radius', scope),
      };

      let linkedR = true;
      const syncRadii = (src) => {
        if (!linkedR) return;
        const val = src.input.value;
        const unit = src.select.value;
        Object.values(radiusFields).forEach(f => {
          f.input.value = val;
          f.select.value = unit;
        });
        this._syncCssFromControls();
      };
      Object.values(radiusFields).forEach(f => {
        f.input.addEventListener('input', () => syncRadii(f));
        f.select.addEventListener('change', () => syncRadii(f));
      });
      linkRadiusBtn.addEventListener('click', () => {
        linkedR = !linkedR;
        //linkRadiusBtn.textContent = linkedR ? '🔗 Link radii' : '🔓 Unlinked radii';
        if (linkedR) linkRadiusBtn.classList.add('cex-active');
        else linkRadiusBtn.classList.remove('cex-active');
      });
    };

    buildForScope(regularPanel, 'regular');
    buildForScope(hoverPanel, 'hover');
  }

  _buildSpacingPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Spacing</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="false">▸</button>
    </div>
    <div class="cex-panel-body">
      <div class="cex-tabs">
        <div class="cex-tablist">
          <button class="cex-tab active" data-tab="regular">Regular</button>
          <button class="cex-tab" data-tab="hover">Hover</button>
        </div>
        <div class="cex-tabpanel active" data-panel="regular"></div>
        <div class="cex-tabpanel" data-panel="hover"></div>
      </div>
    </div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });

    // Tabs switching
    const tablist = wrap.querySelector('.cex-tablist');
    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('.cex-tab');
      if (!btn) return;
      wrap.querySelectorAll('.cex-tab').forEach(t => t.classList.remove('active'));
      wrap.querySelectorAll('.cex-tabpanel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      wrap.querySelector(`.cex-tabpanel[data-panel="${key}"]`).classList.add('active');
      this.state.activeTab = key;
      this._syncCssFromControls();
    });

    const regularPanel = wrap.querySelector('[data-panel="regular"]');
    const hoverPanel = wrap.querySelector('[data-panel="hover"]');

    // Helper to build a unit field
    const buildUnitField = (host, key, label, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope;
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('div');
      control.className = 'cex-unit';
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'Value';
      input.dataset.role = 'value';
      input.style.fontSize = '12px';
      const select = document.createElement('select');
      select.dataset.role = 'unit';
      ['px', '%', 'em', 'rem', 'vh', 'vw'].forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        select.appendChild(opt);
      });
      select.style.fontSize = '12px';
      control.append(input, select);

      input.addEventListener('input', () => this._onFieldChange(row));
      select.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, control);
      host.appendChild(row);
      return { row, input, select };
    };

    // Build section (margin or padding)
    const buildSection = (panel, scope, base) => {
      const heading = document.createElement('h4');
      heading.textContent = base.charAt(0).toUpperCase() + base.slice(1);
      heading.style.margin = '10px 0 6px';
      panel.appendChild(heading);

      const linkBtn = document.createElement('button');
      linkBtn.type = 'button';
      linkBtn.classList.add('cex-link-button');
      linkBtn.classList.add('cex-active');
      linkBtn.innerHTML = `<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22ZM9.198 7.25H9.30203C10.2005 7.24997 10.9497 7.24995 11.5445 7.32991C12.1723 7.41432 12.7391 7.59999 13.1945 8.05546C13.65 8.51093 13.8357 9.07773 13.9201 9.70552C14.0001 10.3003 14 11.0495 14 11.948L14 12C14 12.4142 13.6642 12.75 13.25 12.75C12.8358 12.75 12.5 12.4142 12.5 12C12.5 11.036 12.4984 10.3884 12.4335 9.9054C12.3714 9.44393 12.2642 9.24644 12.1339 9.11612C12.0036 8.9858 11.8061 8.87858 11.3446 8.81654C10.8616 8.7516 10.214 8.75 9.25 8.75C8.28599 8.75 7.63843 8.7516 7.15539 8.81654C6.69393 8.87858 6.49644 8.9858 6.36612 9.11612C6.2358 9.24644 6.12858 9.44393 6.06654 9.9054C6.0016 10.3884 6 11.036 6 12C6 12.964 6.0016 13.6116 6.06654 14.0946C6.12858 14.5561 6.2358 14.7536 6.36612 14.8839C6.49644 15.0142 6.69393 15.1214 7.15539 15.1835C7.63843 15.2484 8.28599 15.25 9.25 15.25C9.66422 15.25 10 15.5858 10 16C10 16.4142 9.66422 16.75 9.25 16.75L9.19798 16.75C8.29951 16.75 7.5503 16.7501 6.95552 16.6701C6.32773 16.5857 5.76093 16.4 5.30546 15.9445C4.84999 15.4891 4.66432 14.9223 4.57991 14.2945C4.49995 13.6997 4.49997 12.9505 4.5 12.052V11.948C4.49997 11.0495 4.49995 10.3003 4.57991 9.70552C4.66432 9.07773 4.84999 8.51093 5.30546 8.05546C5.76093 7.59999 6.32773 7.41432 6.95552 7.32991C7.55029 7.24995 8.29954 7.24997 9.198 7.25ZM16.8446 8.81654C16.3616 8.7516 15.714 8.75 14.75 8.75C14.3358 8.75 14 8.41422 14 8C14 7.58579 14.3358 7.25 14.75 7.25L14.802 7.25C15.7005 7.24997 16.4497 7.24995 17.0445 7.32991C17.6723 7.41432 18.2391 7.59999 18.6945 8.05546C19.15 8.51093 19.3357 9.07773 19.4201 9.70552C19.5001 10.3003 19.5 11.0495 19.5 11.9479V12.052C19.5 12.9505 19.5001 13.6997 19.4201 14.2945C19.3357 14.9223 19.15 15.4891 18.6945 15.9445C18.2391 16.4 17.6723 16.5857 17.0445 16.6701C16.4497 16.7501 15.7005 16.75 14.802 16.75H14.698C13.7995 16.75 13.0503 16.7501 12.4555 16.6701C11.8277 16.5857 11.2609 16.4 10.8055 15.9445C10.35 15.4891 10.1643 14.9223 10.0799 14.2945C9.99995 13.6997 9.99997 12.9505 10 12.052L10 12C10 11.5858 10.3358 11.25 10.75 11.25C11.1642 11.25 11.5 11.5858 11.5 12C11.5 12.964 11.5016 13.6116 11.5665 14.0946C11.6286 14.5561 11.7358 14.7536 11.8661 14.8839C11.9964 15.0142 12.1939 15.1214 12.6554 15.1835C13.1384 15.2484 13.786 15.25 14.75 15.25C15.714 15.25 16.3616 15.2484 16.8446 15.1835C17.3061 15.1214 17.5036 15.0142 17.6339 14.8839C17.7642 14.7536 17.8714 14.5561 17.9335 14.0946C17.9984 13.6116 18 12.964 18 12C18 11.036 17.9984 10.3884 17.9335 9.9054C17.8714 9.44393 17.7642 9.24644 17.6339 9.11612C17.5036 8.9858 17.3061 8.87858 16.8446 8.81654Z"/></svg>`;
      linkBtn.style.fontSize = '11px';
      linkBtn.style.margin = '4px 0';
      panel.appendChild(linkBtn);

      const fields = {
        top: buildUnitField(panel, `${base}-top`, 'Top', scope),
        right: buildUnitField(panel, `${base}-right`, 'Right', scope),
        bottom: buildUnitField(panel, `${base}-bottom`, 'Bottom', scope),
        left: buildUnitField(panel, `${base}-left`, 'Left', scope),
      };

      let linked = true;
      const sync = (src) => {
        if (!linked) return;
        const val = src.input.value;
        const unit = src.select.value;
        Object.values(fields).forEach(f => {
          f.input.value = val;
          f.select.value = unit;
        });
        this._syncCssFromControls();
      };
      Object.values(fields).forEach(f => {
        f.input.addEventListener('input', () => sync(f));
        f.select.addEventListener('change', () => sync(f));
      });
      linkBtn.addEventListener('click', () => {
        linked = !linked;
        //linkBtn.textContent = linked ? `🔗 Link ${base}` : `🔓 Unlinked ${base}`;
        if (linked) linkBtn.classList.add('cex-active');
        else linkBtn.classList.remove('cex-active');
      });
    };

    const buildForScope = (panel, scope) => {
      buildSection(panel, scope, 'margin');
      buildSection(panel, scope, 'padding');
    };

    buildForScope(regularPanel, 'regular');
    buildForScope(hoverPanel, 'hover');
  }

  _buildBoxShadowPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Box Shadow</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="false">▸</button>
    </div>
    <div class="cex-panel-body">
      <div class="cex-tabs">
        <div class="cex-tablist">
          <button class="cex-tab active" data-tab="regular">Regular</button>
          <button class="cex-tab" data-tab="hover">Hover</button>
        </div>
        <div class="cex-tabpanel active" data-panel="regular"></div>
        <div class="cex-tabpanel" data-panel="hover"></div>
      </div>
    </div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });

    // Tabs switching
    const tablist = wrap.querySelector('.cex-tablist');
    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('.cex-tab');
      if (!btn) return;
      wrap.querySelectorAll('.cex-tab').forEach(t => t.classList.remove('active'));
      wrap.querySelectorAll('.cex-tabpanel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      wrap.querySelector(`.cex-tabpanel[data-panel="${key}"]`).classList.add('active');
      this.state.activeTab = key;
      this._syncCssFromControls();
    });

    const regularPanel = wrap.querySelector('[data-panel="regular"]');
    const hoverPanel = wrap.querySelector('[data-panel="hover"]');

    const buildField = (host, f, scope) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = scope;
      row.dataset.prop = f.key;

      const label = document.createElement('div');
      label.className = 'cex-label';
      label.textContent = f.label;
      label.style.fontSize = '13px';

      let control;
      if (f.type === 'color') {
        control = this._buildPopupColorField(row);
      } else if (f.type === 'checkbox') {
        control = document.createElement('input');
        control.type = 'checkbox';
        control.addEventListener('change', () => this._onFieldChange(row));
      } else if (f.type === 'unit') {
        control = document.createElement('div');
        control.className = 'cex-unit';
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Value';
        input.dataset.role = 'value';
        input.style.fontSize = '12px';
        const select = document.createElement('select');
        select.dataset.role = 'unit';
        ['px', 'em', 'rem', '%'].forEach(u => {
          const opt = document.createElement('option');
          opt.value = u;
          opt.textContent = u;
          select.appendChild(opt);
        });
        select.style.fontSize = '12px';
        control.append(input, select);
        input.addEventListener('input', () => this._onFieldChange(row));
        select.addEventListener('change', () => this._onFieldChange(row));
      }

      row.append(label, control);
      host.appendChild(row);
    };

    const fields = [
      { key: 'box-shadow-color', label: 'Color', type: 'color' },
      { key: 'box-shadow-inset', label: 'Inset', type: 'checkbox' },
      { key: 'box-shadow-x', label: 'Position X', type: 'unit' },
      { key: 'box-shadow-y', label: 'Position Y', type: 'unit' },
      { key: 'box-shadow-blur', label: 'Blur', type: 'unit' },
      { key: 'box-shadow-spread', label: 'Spread', type: 'unit' },
    ];

    fields.forEach(f => {
      buildField(regularPanel, f, 'regular');
      buildField(hoverPanel, f, 'hover');
    });
  }

  _buildDisplayPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Display & Layout</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="false">▸</button>
    </div>
    <div class="cex-panel-body"></div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });

    const body = wrap.querySelector('.cex-panel-body');

    // Helper to build a unit field
    const buildUnitField = (key, label) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = 'regular'; // no hover
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('div');
      control.className = 'cex-unit';
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'Value';
      input.dataset.role = 'value';
      input.style.fontSize = '12px';
      const select = document.createElement('select');
      select.dataset.role = 'unit';
      ['px', '%', 'em', 'rem', 'vh', 'vw', 'auto'].forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        select.appendChild(opt);
      });
      select.style.fontSize = '12px';
      control.append(input, select);

      input.addEventListener('input', () => this._onFieldChange(row));
      select.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, control);
      body.appendChild(row);
      return row;
    };

    // Helper to build a select field
    const buildSelectField = (key, label, options) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = 'regular';
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('select');
      options.forEach(val => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val || '—';
        control.appendChild(opt);
      });
      control.style.fontSize = '12px';
      control.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, control);
      body.appendChild(row);
      return row;
    };

    // Helper to build a text field
    const buildTextField = (key, label) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = 'regular';
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('input');
      control.type = 'text';
      control.placeholder = 'Value';
      control.style.fontSize = '12px';
      control.addEventListener('input', () => this._onFieldChange(row));

      row.append(lbl, control);
      body.appendChild(row);
      return row;
    };

    // Core fields
    buildUnitField('width', 'Width');
    buildUnitField('height', 'Height');
    buildUnitField('min-width', 'Min Width');
    buildUnitField('max-width', 'Max Width');
    buildUnitField('min-height', 'Min Height');
    buildUnitField('max-height', 'Max Height');

    const displayRow = buildSelectField('display', 'Display',
      ['', 'none', 'block', 'inline-block', 'flex', 'inline-flex', 'grid', 'inline-grid']
    );
    const positionRow = buildSelectField('position', 'Position',
      ['', 'static', 'relative', 'absolute', 'fixed', 'sticky']
    );

    buildSelectField('overflow', 'Overflow', ['', 'visible', 'hidden', 'scroll', 'auto']);
    buildTextField('z-index', 'Z-index');
    buildSelectField('box-sizing', 'Box sizing', ['', 'content-box', 'border-box']);
    buildTextField('opacity', 'Opacity (0–1)');

    // Flex helpers
    const flexDir = buildSelectField('flex-direction', 'Flex direction',
      ['', 'row', 'column', 'row-reverse', 'column-reverse']);
    const justify = buildSelectField('justify-content', 'Justify content',
      ['', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly']);
    const align = buildSelectField('align-items', 'Align items',
      ['', 'stretch', 'flex-start', 'flex-end', 'center', 'baseline']);

    // Grid helpers
    const gridCols = buildTextField('grid-template-columns', 'Grid columns');
    const gridRows = buildTextField('grid-template-rows', 'Grid rows');
    const gridGap = buildUnitField('gap', 'Gap');
    const justifyItems = buildSelectField('justify-items', 'Justify items',
      ['', 'start', 'end', 'center', 'stretch']);
    const alignItems = buildSelectField('align-items-grid', 'Align items (grid)',
      ['', 'start', 'end', 'center', 'stretch']);
    alignItems.dataset.prop = 'align-items'; // unify with flex align-items

    // Conditional show/hide
    const flexFields = [flexDir, justify, align];
    const gridFields = [gridCols, gridRows, gridGap, justifyItems, alignItems];

    const updateConditional = () => {
      const val = displayRow.querySelector('select').value;
      flexFields.forEach(r => r.style.display = (val.includes('flex') ? '' : 'none'));
      gridFields.forEach(r => r.style.display = (val.includes('grid') ? '' : 'none'));
    };
    displayRow.querySelector('select').addEventListener('change', updateConditional);
    updateConditional();
  }

  _buildTransitionPanel() {
    const content = this.panel.querySelector('.cex-content');

    const wrap = document.createElement('section');
    wrap.className = 'cex-panel';
    wrap.innerHTML = `
    <div class="cex-panel-header">
      <div class="cex-panel-title">Transition</div>
      <button class="cex-panel-toggle" type="button" aria-expanded="false">▸</button>
    </div>
    <div class="cex-panel-body"></div>
  `;
    content.appendChild(wrap);

    // Toggle open/close
    const header = wrap.querySelector('.cex-panel-header');
    const toggle = wrap.querySelector('.cex-panel-toggle');
    header.addEventListener('click', () => {
      wrap.classList.toggle('open');
      const open = wrap.classList.contains('open');
      toggle.textContent = open ? '▾' : '▸';
      toggle.setAttribute('aria-expanded', String(open));
    });

    const body = wrap.querySelector('.cex-panel-body');

    const buildSelect = (key, label, options) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = 'transition'; // special scope
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const select = document.createElement('select');
      options.forEach(val => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val || '—';
        select.appendChild(opt);
      });
      select.style.fontSize = '12px';
      select.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, select);
      body.appendChild(row);
      return select;
    };

    const buildUnit = (key, label, units) => {
      const row = document.createElement('div');
      row.className = 'cex-field-row';
      row.dataset.scope = 'transition';
      row.dataset.prop = key;

      const lbl = document.createElement('div');
      lbl.className = 'cex-label';
      lbl.textContent = label;
      lbl.style.fontSize = '13px';

      const control = document.createElement('div');
      control.className = 'cex-unit';
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'Value';
      input.dataset.role = 'value';
      input.style.fontSize = '12px';
      const select = document.createElement('select');
      select.dataset.role = 'unit';
      units.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        select.appendChild(opt);
      });
      select.style.fontSize = '12px';
      control.append(input, select);

      input.addEventListener('input', () => this._onFieldChange(row));
      select.addEventListener('change', () => this._onFieldChange(row));

      row.append(lbl, control);
      body.appendChild(row);
      return row;
    };

    // Fields
    //const behavior = buildSelect('transition-behavior', 'Behavior',
    //  ['', 'Smooth fade', 'Slide in', 'Pop']
    //);
    const property = buildSelect('transition-property', 'Property',
      ['', 'all', 'opacity', 'color', 'background-color', 'transform', 'width', 'height', 'margin', 'padding', 'border', 'box-shadow']
    );
    buildUnit('transition-duration', 'Duration', ['s', 'ms']);
    buildUnit('transition-delay', 'Delay', ['s', 'ms']);
    buildSelect('transition-timing-function', 'Timing function',
      ['', 'ease', 'linear', 'ease-in', 'ease-out', 'ease-in-out', 'step-start', 'step-end', 'cubic-bezier(0.4,0,0.2,1)']
    );

    // Behavior presets
    /*behavior.addEventListener('change', () => {
      const val = behavior.value;
      const propSel = property;
      const timingSel = body.querySelector('[data-prop="transition-timing-function"] select');
      if (val === 'Smooth fade') {
        propSel.value = 'opacity';
        timingSel.value = 'ease-in-out';
      } else if (val === 'Slide in') {
        propSel.value = 'transform';
        timingSel.value = 'ease-out';
      } else if (val === 'Pop') {
        propSel.value = 'transform';
        timingSel.value = 'cubic-bezier(0.4,0,0.2,1)';
      }
      this._syncCssFromControls();
    });*/
  }

  /* -------------------- Color field with popup -------------------- */

  _buildInlineColorField(row) {
    const wrapper = document.createElement('div');
    wrapper.style.display = 'grid';
    wrapper.style.gap = '6px';

    // Text input for manual hex/rgba
    const text = document.createElement('input');
    text.type = 'text';
    text.placeholder = 'rgba(...) or hex';
    text.dataset.role = 'value';
    text.style.fontSize = '12px';

    // Native color input
    const picker = document.createElement('input');
    picker.type = 'color';
    picker.value = '#000000';
    picker.style.width = '60px';

    // Alpha slider
    const alphaLabel = document.createElement('div');
    alphaLabel.textContent = 'Alpha';
    alphaLabel.style.fontSize = '11px';

    const alpha = document.createElement('input');
    alpha.type = 'range';
    alpha.min = '0';
    alpha.max = '1';
    alpha.step = '0.01';
    alpha.value = '1';
    alpha.style.width = '100%';

    // Sync logic
    const updateFromPicker = () => {
      const hex = picker.value;
      const { r, g, b } = this._hexToRgb(hex);
      const a = parseFloat(alpha.value || '1');
      if (a >= 1) {
        text.value = hex;
      } else {
        text.value = `rgba(${r}, ${g}, ${b}, ${a})`;
      }
      this._onFieldChange(row);
    };

    picker.addEventListener('input', updateFromPicker);
    alpha.addEventListener('input', updateFromPicker);
    text.addEventListener('input', () => this._onFieldChange(row));

    wrapper.append(text, picker, alphaLabel, alpha);
    return wrapper;
  }

  _buildPopupColorField(row) {
    const wrapper = document.createElement('div');
    wrapper.style.display = 'flex';
    wrapper.style.gap = '6px';
    wrapper.style.alignItems = 'center';
    wrapper.style.flexWrap = 'wrap'; // allow inline expansion

    // Text input
    const text = document.createElement('input');
    text.type = 'text';
    text.placeholder = 'rgba(...) or hex';
    text.dataset.role = 'value';
    text.style.fontSize = '12px';

    // Icon button
    const icon = document.createElement('button');
    icon.type = 'button';
    icon.className = 'cex-icon-btn';
    icon.title = 'Pick color';
    icon.innerHTML = `
    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
      <path fill="currentColor" d="M12 3a9 9 0 1 1-6.364 15.364A9 9 0 0 1 12 3zm0 4a5 5 0 1 0 0 10 5 5 0 0 0 0-10z"/>
    </svg>`;

    // Inline panel (hidden by default)
    const inlinePanel = document.createElement('div');
    inlinePanel.style.display = 'none';
    inlinePanel.style.width = '100%';
    inlinePanel.style.marginTop = '6px';
    inlinePanel.style.padding = '6px';
    inlinePanel.style.border = '1px solid var(--border)';
    inlinePanel.style.borderRadius = '6px';
    inlinePanel.style.background = 'var(--panel-bg)';

    const picker = document.createElement('input');
    picker.type = 'color';
    picker.value = '#000000';
    picker.style.width = '60px';

    const alphaLabel = document.createElement('div');
    alphaLabel.textContent = 'Alpha';
    alphaLabel.style.fontSize = '11px';

    const alpha = document.createElement('input');
    alpha.type = 'range';
    alpha.min = '0';
    alpha.max = '1';
    alpha.step = '0.01';
    alpha.value = '1';
    alpha.style.width = '100%';

    inlinePanel.append(picker, alphaLabel, alpha);

    // Sync logic
    const updateFromPicker = () => {
      const hex = picker.value;
      const { r, g, b } = this._hexToRgb(hex);
      const a = parseFloat(alpha.value || '1');
      if (a >= 1) {
        text.value = hex;
      } else {
        text.value = `rgba(${r}, ${g}, ${b}, ${a})`;
      }
      this._onFieldChange(row);
    };

    picker.addEventListener('input', updateFromPicker);
    alpha.addEventListener('input', updateFromPicker);
    text.addEventListener('input', () => this._onFieldChange(row));

    // Toggle inline panel
    icon.addEventListener('click', (e) => {
      e.preventDefault();
      inlinePanel.style.display = inlinePanel.style.display === 'none' ? 'block' : 'none';
    });

    wrapper.append(text, icon, inlinePanel);
    return wrapper;
  }

  _hexToRgb(hex) {
    const h = hex.replace('#', '').trim();
    const r = parseInt(h.substring(0, 2), 16);
    const g = parseInt(h.substring(2, 4), 16);
    const b = parseInt(h.substring(4, 6), 16);
    return { r, g, b };
  }

  _onFieldChange(row) {
    // If background-image changed, toggle conditional fields
    if (row.dataset.prop === 'background-image') {
      const val = row.querySelector('input').value.trim();
      const panel = row.closest('.cex-tabpanel');
      panel.querySelectorAll('.cex-field-row[data-conditional="background-image"]').forEach(r => {
        r.style.display = val ? '' : 'none';
      });
    }
    this._syncCssFromControls();
  }


  _collectPanelValues(scope) {
    const out = {};
    this.panel.querySelectorAll(`.cex-field-row[data-scope="${scope}"]`).forEach(r => {
      const prop = r.dataset.prop;
      let val = '';

      const unitWrap = r.querySelector('.cex-unit');
      if (unitWrap) {
        const v = unitWrap.querySelector('[data-role="value"]').value.trim();
        const u = unitWrap.querySelector('[data-role="unit"]').value.trim();
        val = v ? (u ? `${v}${u}` : v) : '';
      } else {
        const textInput = r.querySelector('input[type="text"]');
        const select = r.querySelector('select');
        const checkbox = r.querySelector('input[type="checkbox"]');
        if (checkbox) {
          val = checkbox.checked ? 'true' : '';  // Adjust if needed; currently unused but future-proof
        } else if (select && !select.dataset.role) {
          val = select.value.trim();
        } else if (textInput) {
          val = textInput.value.trim();
        }
      }

      if (val) out[prop] = val;
    });
    return out;
  }

  _cssFromValues(values) {
    const lines = [];
    const useImportant = this.importantToggle?.checked;
    const suffix = useImportant ? ' !important' : ''

    // --- Box Shadow special handling ---
    const hasBoxShadow = values['box-shadow-x'] || values['box-shadow-y'] ||
      values['box-shadow-blur'] || values['box-shadow-spread'] ||
      values['box-shadow-color'];
    if (hasBoxShadow) {
      const inset = values['box-shadow-inset'] ? 'inset ' : '';
      const x = values['box-shadow-x'] || '0px';
      const y = values['box-shadow-y'] || '0px';
      const blur = values['box-shadow-blur'] || '0px';
      const spread = values['box-shadow-spread'] || '0px';
      const color = values['box-shadow-color'] || 'rgba(0,0,0,0.5)';
      lines.push(`box-shadow: ${inset}${x} ${y} ${blur} ${spread} ${color}${suffix};`);
    }

    // Transition handling (single)
    if (values['transition-property'] && values['transition-duration']) {
      const prop = values['transition-property'];
      const dur = values['transition-duration'];
      const timing = values['transition-timing-function'] || 'ease';
      const delay = values['transition-delay'] || '0s';
      lines.push(`transition: ${prop} ${dur} ${timing} ${delay}${suffix};`);
    }

    // --- Display / Layout special handling ---
    if (values['display']) {
      lines.push(`display: ${values['display']};`);

      if (values['display'].includes('flex')) {
        if (values['flex-direction']) lines.push(`flex-direction: ${values['flex-direction']}${suffix};`);
        if (values['justify-content']) lines.push(`justify-content: ${values['justify-content']}${suffix};`);
        if (values['align-items']) lines.push(`align-items: ${values['align-items']}${suffix};`);
      }

      if (values['display'].includes('grid')) {
        if (values['grid-template-columns']) lines.push(`grid-template-columns: ${values['grid-template-columns']}${suffix};`);
        if (values['grid-template-rows']) lines.push(`grid-template-rows: ${values['grid-template-rows']}${suffix};`);
        if (values['gap']) lines.push(`gap: ${values['gap']}${suffix};`);
        if (values['justify-items']) lines.push(`justify-items: ${values['justify-items']}${suffix};`);
        if (values['align-items']) lines.push(`align-items: ${values['align-items']}${suffix};`);
      }
    }

    // --- General layout fields ---
    const layoutProps = [
      'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height',
      'position', 'overflow', 'z-index', 'box-sizing', 'opacity'
    ];
    layoutProps.forEach(prop => {
      if (values[prop]) lines.push(`${prop}: ${values[prop]}${suffix};`);
    });

    // --- All other properties (skip ones already handled) ---
    for (const [prop, val] of Object.entries(values)) {
      if (!val) continue;
      if (prop.startsWith('box-shadow')) continue;
      if (prop === 'display' || prop === 'flex-direction' || prop === 'justify-content' ||
        prop === 'align-items' || prop === 'grid-template-columns' || prop === 'grid-template-rows' ||
        prop === 'gap' || prop === 'justify-items' || prop === 'align-items-grid' ||
        layoutProps.includes(prop)) continue;
      lines.push(`${prop}: ${val}${suffix};`);
    }

    return lines.join('\n');
  }

  _syncCssFromControls() {
    const baseSelector = (this.state.currentSelector || '').trim();
    if (!baseSelector) {
      this.cssOutput.value = '';
      this._clearLiveStyles();
      return;
    }

    const formatted = this._formatCombinedCssBlocks();
    this.cssOutput.value = formatted;
    const blocks = this._splitBlocks(formatted);
    this.state.liveStyles.clear();
    blocks.forEach(({ selector, cssText }) => {
      if (selector && cssText.trim()) this.state.liveStyles.set(selector, cssText.trim());
    });
    const styleEl = this._getOrCreateStyleTag('live');
    styleEl.textContent = this._composeCss(this.state.liveStyles);
  }

  _resetControls() {
    // Clear all field inputs
    this.panel.querySelectorAll('.cex-field-row').forEach(row => {
      const inputs = row.querySelectorAll('input, select');
      inputs.forEach(inp => {
        if (inp.tagName === 'SELECT') {
          inp.selectedIndex = 0;
        } else if (inp.type === 'range') {
          inp.value = '1';
        } else if (inp.type === 'color') {
          inp.value = '#000000';
        } else {
          inp.value = '';
        }
      });
    });

    // Clear textarea
    this.cssOutput.value = '';

    // Clear live preview
    this._clearLiveStyles();
  }

  /* -------------------- Public API -------------------- */

  getGeneratedCss() {
    return this._composeCss(this.state.savedStyles).trim();
  }
}

/* Auto-load when file is included */
window.addEventListener('DOMContentLoaded', () => {
  if (!window.__cssEditorInstance) {
    window.__cssEditorInstance = new CssEditor();
  }
});

function saveSCCData(content) {
  if (typeof sccCustomizerData === 'undefined') {
    console.error('Customizer data not found.');
    return;
  }

  function reloadWithoutParams(paramsToRemove = []) {
    const url = new URL(window.location.href);

    // Remove specified parameters
    paramsToRemove.forEach(param => url.searchParams.delete(param));

    // Reload the page with the updated URL
    window.location.href = url.pathname + url.search;
  }

  if (SCCCustomizer && SCCCustomizer.preloader) SCCCustomizer.preloader.show('Saving data ...');

  const { nonce, post_id, rest_url } = sccCustomizerData;

  fetch(rest_url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
    },
    body: JSON.stringify({
      post_id: post_id,
      content: content,
    }),
  })
    .then(response => {
      if (!response.ok) {
        if (SCCCustomizer && SCCCustomizer.preloader) SCCCustomizer.preloader.hide();
        throw new Error(`Server responded with status ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        console.log('✅ CSS saved successfully:', data.message);
        if (SCCCustomizer && SCCCustomizer.preloader) SCCCustomizer.preloader.hide();
        alert('CSS Saved. Press to reload');
        reloadWithoutParams(['scc_customizer', 'scc']);
      } else {
        console.warn('⚠️ Save failed:', data);
        if (SCCCustomizer && SCCCustomizer.preloader) SCCCustomizer.preloader.hide();
        alert('CSS save failed. Press to reload');
        reloadWithoutParams(['scc_customizer', 'scc']);
      }
    })
    .catch(error => {
      console.error('❌ Error saving CSS:', error);
      if (SCCCustomizer && SCCCustomizer.preloader) SCCCustomizer.preloader.hide();
      alert('CSS save error. Press to reload');
      reloadWithoutParams(['scc_customizer', 'scc']);

    });
}