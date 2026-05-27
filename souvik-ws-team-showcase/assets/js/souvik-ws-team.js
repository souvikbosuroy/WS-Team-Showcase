/**
 * Souvik WS Team Showcase — front-end JS
 * Namespace : SouvikWSTeam
 * No jQuery. ES6+. GSAP loaded conditionally by PHP.
 *
 * @version 2.1.0
 */
( () => {
	'use strict';

	/* =========================================================================
	   CONSTANTS
	   ========================================================================= */

	const SELECTOR_MAP = {
		card:        '.souvik-ws-team-card',
		image:       '.souvik-ws-team-card__image',
		name:        '.souvik-ws-team-card__name',
		designation: '.souvik-ws-team-card__role',
		badge:       '.souvik-ws-team-dept-badge',
		bio:         '.souvik-ws-team-card__bio',
		socials:     '.souvik-ws-team-card__social a',
		button:      '.souvik-ws-team-card__btn',
		filter:      '.souvik-ws-filter-btn',
		popup:       '.souvik-ws-popup-modal',
	};

	/* =========================================================================
	   ANIMATION MANAGER — uses autoAlpha + clearProps to prevent invisible cards
	   ========================================================================= */

	class AnimationManager {
		/**
		 * @param {HTMLElement} wrapperEl
		 * @param {Object}      config    — parsed from data-souvik-ws-anim
		 */
		init( wrapperEl, config ) {
			if ( typeof gsap === 'undefined' ) return;

			if ( typeof ScrollTrigger !== 'undefined' ) {
				gsap.registerPlugin( ScrollTrigger );
			}

			const isEditMode = typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode();
			const reducedMotionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
			if ( reducedMotionQuery.matches ) return;

			for ( const [ key, enabled ] of Object.entries( config ) ) {
				if ( ! enabled ) continue;

				const targets = this.getTargets( wrapperEl, key );
				if ( ! targets.length ) continue;

				// Build ScrollTrigger options — disabled in editor
				const triggerOpts = isEditMode ? null : {
					trigger: key === 'filter' ? targets[0] : wrapperEl,
					start: key === 'filter' ? 'top 90%' : 'top 85%',
					once: true,
				};

				// clearProps: 'all' ensures no invisible residue if animation is interrupted
				switch ( key ) {
					case 'card':
						gsap.fromTo( targets,
							{ autoAlpha: 0, y: 50, scale: 0.95 },
							{
								autoAlpha: 1, y: 0, scale: 1,
								duration: 0.8, ease: 'power3.out', stagger: 0.1,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'image':
						gsap.fromTo( targets,
							{ autoAlpha: 0, scale: 1.15 },
							{
								autoAlpha: 1, scale: 1,
								duration: 1.0, ease: 'power2.out', stagger: 0.08,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'name':
						gsap.fromTo( targets,
							{ autoAlpha: 0, y: 20 },
							{
								autoAlpha: 1, y: 0,
								duration: 0.6, ease: 'power2.out', stagger: 0.05,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'designation':
						gsap.fromTo( targets,
							{ autoAlpha: 0, y: 15 },
							{
								autoAlpha: 1, y: 0,
								duration: 0.6, ease: 'power2.out', stagger: 0.05,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'badge':
						gsap.fromTo( targets,
							{ autoAlpha: 0, scale: 0.4 },
							{
								autoAlpha: 1, scale: 1,
								duration: 0.6, ease: 'back.out(1.7)', stagger: 0.05,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'bio':
						gsap.fromTo( targets,
							{ autoAlpha: 0, y: 15 },
							{
								autoAlpha: 1, y: 0,
								duration: 0.8, ease: 'power2.out', stagger: 0.05,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'socials':
						gsap.fromTo( targets,
							{ autoAlpha: 0, scale: 0 },
							{
								autoAlpha: 1, scale: 1,
								duration: 0.6, ease: 'back.out(2)', stagger: 0.04,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'button':
						gsap.fromTo( targets,
							{ autoAlpha: 0, scale: 0.6 },
							{
								autoAlpha: 1, scale: 1,
								duration: 0.8, ease: 'elastic.out(1, 0.6)', stagger: 0.06,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					case 'filter':
						gsap.fromTo( targets,
							{ autoAlpha: 0, y: -25 },
							{
								autoAlpha: 1, y: 0,
								duration: 0.7, ease: 'power3.out', stagger: 0.06,
								clearProps: 'all',
								scrollTrigger: triggerOpts,
							}
						);
						break;

					// Popup modal has its own animation triggered on open in PopupManager.
					case 'popup':
					default:
						break;
				}
			}
		}

		/** Resolve DOM targets for a given element key. */
		getTargets( wrapperEl, key ) {
			const selector = SELECTOR_MAP[ key ];
			if ( ! selector ) return [];
			return [ ...wrapperEl.querySelectorAll( selector ) ];
		}
	}

	/* =========================================================================
	   COORDINATED STATE ENGINE
	   ========================================================================= */

	class TeamShowcaseController {
		constructor( wrapperEl ) {
			this.wrapper = wrapperEl;
			this.cards = [ ...wrapperEl.querySelectorAll( '.souvik-ws-team-card' ) ];

			// Configuration
			this.perPage = parseInt( wrapperEl.dataset.itemsPerPage ?? '6', 10 );
			this.pagType = wrapperEl.dataset.pagType ?? 'none';

			// State values
			this.currentFilter = '';
			this.currentSearch = '';
			this.currentPage = 1;
			this.visibleLimit = this.perPage;

			this.init();
		}

		init() {
			// Initialize Filter elements
			const btns = this.wrapper.querySelectorAll( '.souvik-ws-filter-btn' );
			btns.forEach( btn => {
				btn.addEventListener( 'click', () => {
					btns.forEach( b => {
						b.classList.remove( 'is-active' );
						b.setAttribute( 'aria-selected', 'false' );
					} );
					btn.classList.add( 'is-active' );
					btn.setAttribute( 'aria-selected', 'true' );
					this.currentFilter = btn.dataset.filter ?? '';
					this.currentPage = 1;
					this.visibleLimit = this.perPage;
					this.update();
				} );
			} );

			const select = this.wrapper.querySelector( '.souvik-ws-filter-select' );
			if ( select ) {
				select.addEventListener( 'change', () => {
					this.currentFilter = select.value;
					this.currentPage = 1;
					this.visibleLimit = this.perPage;
					this.update();
				} );
			}

			// Initialize Search element
			const searchInput = this.wrapper.querySelector( '.souvik-ws-search-input' );
			if ( searchInput ) {
				searchInput.addEventListener( 'input', () => {
					this.currentSearch = searchInput.value.toLowerCase().trim();
					this.currentPage = 1;
					this.visibleLimit = this.perPage;
					this.update();
				} );
			}

			// Initialize Load More button
			const loadMoreBtn = this.wrapper.querySelector( '.souvik-ws-load-more-btn' );
			if ( loadMoreBtn ) {
				loadMoreBtn.addEventListener( 'click', () => {
					this.visibleLimit += this.perPage;
					this.update();
				} );
			}

			// Initial state sync
			this.update();
		}

		/**
		 * Orchestrates search, filter, and pagination globally per wrapper
		 */
		update() {
			// 1. Filter and Search matching cards
			const matchedCards = this.cards.filter( card => {
				const matchesFilter = ! this.currentFilter || card.dataset.dept === this.currentFilter;
				
				let matchesSearch = true;
				if ( this.currentSearch ) {
					const searchableText = [
						card.querySelector( '.souvik-ws-team-card__name' )?.textContent ?? '',
						card.querySelector( '.souvik-ws-team-card__role' )?.textContent ?? '',
						card.querySelector( '.souvik-ws-team-card__bio' )?.textContent ?? '',
						card.dataset.dept ?? ''
					].join(' ').toLowerCase();

					matchesSearch = searchableText.includes( this.currentSearch );
				}

				return matchesFilter && matchesSearch;
			} );

			// 2. Hide all cards initially
			this.cards.forEach( card => {
				card.style.display = 'none';
				card.classList.add( 'souvik-ws-team-card--hidden' );
			} );

			// 3. Paginate and show matched cards
			if ( this.pagType === 'load_more' ) {
				// Show up to the current visible limit
				matchedCards.forEach( ( card, idx ) => {
					if ( idx < this.visibleLimit ) {
						card.style.display = '';
						card.classList.remove( 'souvik-ws-team-card--hidden' );
					}
				} );

				// Hide or Show load more wrapper dynamically based on matching items count
				const loadMoreWrap = this.wrapper.querySelector( '.souvik-ws-load-more-wrap' );
				if ( loadMoreWrap ) {
					if ( matchedCards.length > this.visibleLimit ) {
						loadMoreWrap.style.display = 'flex';
					} else {
						loadMoreWrap.style.display = 'none';
					}
				}
			} else if ( this.pagType === 'numbers' ) {
				const totalPages = Math.ceil( matchedCards.length / this.perPage );
				if ( this.currentPage > totalPages && totalPages > 0 ) {
					this.currentPage = totalPages;
				}

				const startIdx = ( this.currentPage - 1 ) * this.perPage;
				const endIdx = this.currentPage * this.perPage;

				matchedCards.forEach( ( card, idx ) => {
					if ( idx >= startIdx && idx < endIdx ) {
						card.style.display = '';
						card.classList.remove( 'souvik-ws-team-card--hidden' );
					}
				} );

				// Rebuild/render pagination numbers bar dynamically
				this.renderPaginationBar( totalPages );
			} else {
				// No pagination: show all matched cards
				matchedCards.forEach( card => {
					card.style.display = '';
					card.classList.remove( 'souvik-ws-team-card--hidden' );
				} );
			}

			// 4. Force GSAP ScrollTrigger to recalculate bounds since cards height/layout changed
			if ( typeof ScrollTrigger !== 'undefined' ) {
				ScrollTrigger.refresh();
			}
		}

		/** Rebuild numbers pagination buttons */
		renderPaginationBar( totalPages ) {
			let pagEl = this.wrapper.querySelector( '.souvik-ws-pagination' );
			if ( ! pagEl ) return;

			if ( totalPages <= 1 ) {
				pagEl.style.display = 'none';
				return;
			}

			pagEl.style.display = 'flex';
			pagEl.innerHTML = '';

			for ( let pg = 1; pg <= totalPages; pg++ ) {
				const btn = document.createElement( 'button' );
				btn.className = 'souvik-ws-page-btn' + ( pg === this.currentPage ? ' is-active' : '' );
				btn.dataset.page = pg.toString();
				btn.textContent = pg.toString();
				btn.addEventListener( 'click', () => {
					this.currentPage = pg;
					this.update();
					// Scroll smoothly to widget top
					this.wrapper.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				} );
				pagEl.appendChild( btn );
			}
		}
	}

	/* =========================================================================
	   LAZY IMAGE LOADER
	   ========================================================================= */

	class LazyLoader {
		constructor( wrapperEl ) {
			const imgs = [ ...wrapperEl.querySelectorAll( '.souvik-ws-team-card__img[loading="lazy"]' ) ];
			if ( ! imgs.length ) return;

			if ( 'IntersectionObserver' in window ) {
				const io = new IntersectionObserver(
					( entries ) => {
						entries.forEach( entry => {
							if ( entry.isIntersecting ) {
								const img = entry.target;
								img.addEventListener( 'load', () => img.classList.add( 'is-loaded' ), { once: true } );
								if ( img.complete ) img.classList.add( 'is-loaded' );
								io.unobserve( img );
							}
						} );
					},
					{ rootMargin: '200px' }
				);
				imgs.forEach( img => io.observe( img ) );
			} else {
				imgs.forEach( img => img.classList.add( 'is-loaded' ) );
			}
		}
	}

	/* =========================================================================
	   STICKY FILTER BAR MANAGER
	   Uses IntersectionObserver to detect when the widget enters/leaves viewport.
	   The filter bar sticks to top while user is within the widget area, and
	   releases (unsticks) as soon as the widget scrolls out of view.
	   ========================================================================= */

	class StickyFilterManager {
		constructor( wrapperEl ) {
			this.wrapper = wrapperEl;

			// Only activate if sticky is enabled
			if ( wrapperEl.dataset.filterSticky !== '1' ) return;

			this.filterBar = wrapperEl.querySelector( '.souvik-ws-filter-bar--sticky-enabled' );
			if ( ! this.filterBar ) return;

			this.placeholder = null;
			this.isSticky = false;

			this.init();
		}

		init() {
			// Reserve a placeholder to prevent layout shift when filter goes sticky
			this.placeholder = document.createElement( 'div' );
			this.placeholder.className = 'souvik-ws-filter-placeholder';
			this.placeholder.style.cssText = 'display:none; pointer-events:none; visibility:hidden;';
			this.filterBar.parentNode.insertBefore( this.placeholder, this.filterBar );

			// Use IntersectionObserver on the wrapper to know when widget is in viewport
			// threshold: 0 = any pixel visible; we update sticky state on scroll
			this._boundScroll = this._onScroll.bind( this );

			const io = new IntersectionObserver(
				( entries ) => {
					const entry = entries[0];
					if ( entry.isIntersecting ) {
						// Widget is visible — start listening to scroll
						window.addEventListener( 'scroll', this._boundScroll, { passive: true } );
						this._onScroll();
					} else {
						// Widget is out of view — remove sticky, stop listening
						window.removeEventListener( 'scroll', this._boundScroll );
						this._unstick();
					}
				},
				{ threshold: 0 }
			);

			io.observe( this.wrapper );
		}

		_onScroll() {
			const wrapRect = this.wrapper.getBoundingClientRect();
			const filterHeight = this.filterBar.offsetHeight;
			const headerOffset = this._getHeaderHeight();

			const inFlowEl = this.isSticky ? this.placeholder : this.filterBar;
			const inFlowRect = inFlowEl.getBoundingClientRect();

			// Only stick when the filter bar's top touches or goes behind the bottom of the sticky header
			const topCrossed = inFlowRect.top <= headerOffset;
			const bottomNotPassed = wrapRect.bottom > ( filterHeight + headerOffset + 20 );
			const shouldStick = topCrossed && bottomNotPassed;

			if ( shouldStick ) {
				this._stick( headerOffset );
			} else {
				this._unstick();
			}
		}

		_stick( headerOffset ) {
			if ( this.isSticky ) {
				// Update top position dynamically while scrolling in case header changes size
				this.filterBar.style.setProperty( '--souvik-ws-sticky-top', headerOffset + 'px' );
				return;
			}
			this.isSticky = true;

			this.filterBar.style.setProperty( '--souvik-ws-sticky-top', headerOffset + 'px' );

			const filterHeight = this.filterBar.offsetHeight;
			this.placeholder.style.display = 'block';
			this.placeholder.style.height = filterHeight + 'px';

			this.filterBar.classList.add( 'is-sticky' );
		}

		_isStickyOrFixed( el ) {
			if ( ! el ) return false;
			let current = el;
			while ( current && current !== document.body && current !== document.documentElement ) {
				const cs = window.getComputedStyle( current );
				if ( cs.position === 'fixed' || cs.position === 'sticky' || current.classList.contains( 'elementor-sticky--active' ) ) {
					return true;
				}
				current = current.parentElement;
			}
			return false;
		}

		_getHeaderHeight() {
			let adminBarHeight = 0;

			// 1. WP Admin Bar (only when admin is logged in and admin bar is showing)
			const isAdminBarShowing = document.body.classList.contains( 'admin-bar' );
			if ( isAdminBarShowing ) {
				const adminBar = document.querySelector( '#wpadminbar' );
				if ( adminBar ) {
					const rect = adminBar.getBoundingClientRect();
					if ( rect.height > 0 ) {
						adminBarHeight = rect.height;
					}
				}
			}

			// 2. Main target "header.uv-site-header" or ".uv-site-header"
			const mainHeader = document.querySelector( 'header.uv-site-header' ) || document.querySelector( '.uv-site-header' );
			if ( mainHeader ) {
				const rect = mainHeader.getBoundingClientRect();
				if ( rect.height > 0 ) {
					if ( this._isStickyOrFixed( mainHeader ) && rect.bottom > adminBarHeight ) {
						return rect.bottom;
					}
				}
			} else {
				// Fallback to other possible header elements
				const candidates = [
					'header.header-uv-site-header',
					'.header-uv-site-header',
					'div.umavik-header',
					'header.umavik-header',
					'.site-header',
					'#masthead',
					'.elementor-location-header',
					'.main-header-bar',
					'.header-navigation',
					'.elementor-sticky--active',
				];

				for ( const sel of candidates ) {
					const el = document.querySelector( sel );
					if ( ! el ) continue;

					const rect = el.getBoundingClientRect();
					if ( rect.height <= 0 ) continue;

					if ( this._isStickyOrFixed( el ) && rect.bottom > adminBarHeight ) {
						return rect.bottom;
					}
				}
			}

			return adminBarHeight;
		}

		_unstick() {
			if ( ! this.isSticky ) return;
			this.isSticky = false;

			this.placeholder.style.display = 'none';
			this.filterBar.classList.remove( 'is-sticky' );
			this.filterBar.style.removeProperty( '--souvik-ws-sticky-top' );
		}

	}

	/* =========================================================================
	   POPUP MANAGER
	   ========================================================================= */

	class PopupManager {
		constructor( wrapperEl ) {
			this.wrapper  = wrapperEl;
			this.overlay  = wrapperEl.querySelector( '.souvik-ws-popup-overlay' );
			this.modal    = wrapperEl.querySelector( '.souvik-ws-popup-modal' );
			this.closeBtn = wrapperEl.querySelector( '.souvik-ws-popup-close' );
			this.content  = wrapperEl.querySelector( '.souvik-ws-popup-modal__content' );

			if ( ! this.modal || ! this.content ) return;

			try {
				this.popupFields = JSON.parse( this.content.dataset.popupFields || '[]' );
			} catch ( e ) {
				this.popupFields = [];
			}
			this.trigger = wrapperEl.dataset.popupTrigger ?? 'card_click';

			try {
				const animDataAttr = wrapperEl.dataset.souvikWsAnim;
				const animConfig = animDataAttr ? JSON.parse( animDataAttr ) : {};
				this.popupAnimEnabled = animConfig.popup === true;
			} catch ( e ) {
				this.popupAnimEnabled = false;
			}

			this.init();
		}

		init() {
			// Open triggers
			const openAttr = '[data-souvik-ws-popup-open="true"]';
			this.wrapper.querySelectorAll( openAttr ).forEach( el => {
				el.addEventListener( 'click', e => {
					const card = el.closest( '.souvik-ws-team-card' ) ?? el;
					this.open( card );
					e.preventDefault();
				} );
			} );

			// Keyboard support
			if ( this.trigger === 'card_click' ) {
				this.wrapper.querySelectorAll( '.souvik-ws-team-card[data-souvik-ws-popup-open]' ).forEach( card => {
					card.addEventListener( 'keydown', e => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.preventDefault();
							this.open( card );
						}
					} );
				} );
			}

			// Close triggers
			this.closeBtn?.addEventListener( 'click', () => this.close() );
			this.overlay?.addEventListener( 'click', () => this.close() );
			document.addEventListener( 'keydown', e => {
				if ( e.key === 'Escape' ) this.close();
			} );
		}

		open( cardEl ) {
			const name        = cardEl.querySelector( '.souvik-ws-team-card__name' )?.textContent ?? '';
			const role        = cardEl.querySelector( '.souvik-ws-team-card__role' )?.textContent ?? '';
			const badge       = cardEl.querySelector( '.souvik-ws-team-dept-badge' )?.textContent ?? '';
			const bioEl       = cardEl.querySelector( '.souvik-ws-team-card__bio' );
			const imageEl     = cardEl.querySelector( '.souvik-ws-team-card__img' );
			const socialsEl   = cardEl.querySelector( '.souvik-ws-team-card__social' );
			const btnEl       = cardEl.querySelector( '.souvik-ws-team-card__btn' );
			const extraEls    = cardEl.querySelectorAll( '.souvik-ws-extra-field' );

			const fields = this.popupFields;
			let html = '';

			if ( imageEl ) {
				html += `<div class="souvik-ws-team-card__image"><img class="souvik-ws-team-card__img is-loaded" src="${imageEl.src}" alt="${imageEl.alt}"></div>`;
			}

			let infoHtml = '';
			if ( fields.includes( 'name' ) && name ) {
				infoHtml += `<h3 class="souvik-ws-team-card__name">${name}</h3>`;
			}
			if ( fields.includes( 'designation' ) && role ) {
				infoHtml += `<p class="souvik-ws-team-card__role">${role}</p>`;
			}
			if ( fields.includes( 'department' ) && badge ) {
				infoHtml += `<span class="souvik-ws-team-dept-badge">${badge}</span>`;
			}
			if ( fields.includes( 'bio' ) && bioEl ) {
				infoHtml += `<p class="souvik-ws-team-card__bio">${bioEl.innerHTML}</p>`;
			}

			// Render Custom / Extra Fields in popup too
			if ( extraEls.length > 0 ) {
				extraEls.forEach( extra => {
					infoHtml += `<p class="${extra.className}">${extra.innerHTML}</p>`;
				} );
			}

			if ( fields.includes( 'socials' ) && socialsEl ) {
				infoHtml += `<ul class="souvik-ws-team-card__social">${socialsEl.innerHTML}</ul>`;
			}
			if ( fields.includes( 'button' ) && btnEl ) {
				infoHtml += `<div class="souvik-ws-team-card__btn-wrap"><a class="souvik-ws-team-card__btn" href="${btnEl.href}">${btnEl.textContent}</a></div>`;
			}

			if ( infoHtml ) {
				html += `<div class="souvik-ws-popup-info-wrap">${infoHtml}</div>`;
			}

			if ( this.content ) this.content.innerHTML = html;

			this.overlay?.classList.add( 'is-visible' );
			this.modal?.classList.add( 'is-visible' );
			this.modal?.setAttribute( 'aria-hidden', 'false' );

			if ( this.popupAnimEnabled && typeof gsap !== 'undefined' ) {
				// Animate open: scale up and fade in
				gsap.fromTo( this.modal,
					{ opacity: 0, scale: 0.88, y: 30 },
					{
						opacity: 1, scale: 1, y: 0,
						duration: 0.4, ease: 'power3.out',
						// After open: clear GSAP inline styles so CSS close transition works cleanly
						onComplete: () => gsap.set( this.modal, { clearProps: 'opacity,scale,y,transform' } ),
					}
				);
				if ( this.overlay ) {
					gsap.fromTo( this.overlay,
						{ opacity: 0 },
						{
							opacity: 1, duration: 0.3, ease: 'power2.out',
							onComplete: () => gsap.set( this.overlay, { clearProps: 'opacity' } ),
						}
					);
				}
			}

			// Prevent background page jerking by compensating scrollbar width
			const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
			if ( scrollbarWidth > 0 ) {
				document.body.style.paddingRight = `${scrollbarWidth}px`;
			}
			document.body.style.overflow = 'hidden';

			this.closeBtn?.focus();
		}

		close() {
			const doClose = () => {
				this.overlay?.classList.remove( 'is-visible' );
				this.modal?.classList.remove( 'is-visible' );
				this.modal?.setAttribute( 'aria-hidden', 'true' );
				document.body.style.overflow = '';
				document.body.style.paddingRight = '';
			};

			// If GSAP is available and popup animation is enabled, animate the close.
			// We must kill any running tweens first so the close always wins.
			if ( this.popupAnimEnabled && typeof gsap !== 'undefined' && this.modal ) {
				gsap.killTweensOf( this.modal );
				if ( this.overlay ) gsap.killTweensOf( this.overlay );

				gsap.to( this.modal, {
					opacity: 0, scale: 0.88, y: 20,
					duration: 0.28, ease: 'power3.in',
					onComplete: () => {
						doClose();
						// Clear inline styles GSAP set, so modal is ready for next open
						gsap.set( this.modal, { clearProps: 'all' } );
					},
				} );
				if ( this.overlay ) {
					gsap.to( this.overlay, { opacity: 0, duration: 0.2, ease: 'power2.in' } );
				}
			} else {
				// Fallback: CSS transition-based close (no GSAP)
				doClose();
			}
		}
	}

	/* =========================================================================
	   SKELETON REMOVAL
	   ========================================================================= */

	const removeSkeleton = ( wrapperEl ) => {
		const skeletonGrid = wrapperEl.querySelector( '.souvik-ws-skeleton-grid' );
		if ( skeletonGrid ) {
			setTimeout( () => {
				skeletonGrid.style.transition = 'opacity 0.3s';
				skeletonGrid.style.opacity = '0';
				setTimeout( () => {
					skeletonGrid.remove();
					wrapperEl.classList.remove( 'is-loading' );
				}, 300 );
			}, 400 );
		}
	};

	/* =========================================================================
	   GLOBAL DARK TOGGLE ENGINE (Page-wide Sync)
	   ========================================================================= */

	class DarkToggleEngine {
		constructor( wrapperEl ) {
			this.wrapper = wrapperEl;
			this.btn     = wrapperEl.querySelector( '.souvik-ws-dark-toggle-btn' );
			if ( this.btn ) this.init();
		}

		init() {
			// Read the global state of the body on instantiation
			const isBodyDark = document.body.getAttribute( 'data-souvik-ws-dark' ) === '1';
			this.applyLocalState( isBodyDark );

			let lastToggleTime = 0;
			const handleToggle = (e) => {
				const now = Date.now();
				if ( now - lastToggleTime < 300 ) {
					e.preventDefault();
					return;
				}
				lastToggleTime = now;
				e.preventDefault();

				const currentDark = document.body.getAttribute( 'data-souvik-ws-dark' ) === '1';
				const nextDark = ! currentDark;

				// 1. Save globally in localStorage
				localStorage.setItem( 'souvik-ws-dark', nextDark ? '1' : '0' );

				// 2. Apply page-wide to Body
				document.body.setAttribute( 'data-souvik-ws-dark', nextDark ? '1' : '0' );

				// 3. Sync all toggle wrappers and buttons dynamically
				document.querySelectorAll( '.souvik-ws-team-wrapper' ).forEach( wrap => {
					wrap.setAttribute( 'data-dark', nextDark ? '1' : '0' );
					const toggleBtn = wrap.querySelector( '.souvik-ws-dark-toggle-btn' );
					if ( toggleBtn ) {
						toggleBtn.setAttribute( 'aria-checked', nextDark ? 'true' : 'false' );
					}
				} );
			};

			this.btn.addEventListener( 'click', handleToggle );
			this.btn.addEventListener( 'touchend', handleToggle );
		}

		applyLocalState( isDark ) {
			this.wrapper.setAttribute( 'data-dark', isDark ? '1' : '0' );
			this.btn.setAttribute( 'aria-checked', isDark ? 'true' : 'false' );
		}
	}

	/* =========================================================================
	   BOOTSTRAP — initialise every wrapper on the page
	   ========================================================================= */

	const initWrapper = ( wrapperEl ) => {
		if ( wrapperEl.dataset.souvikWsInitialized === 'true' ) return;
		wrapperEl.dataset.souvikWsInitialized = 'true';

		// Animation config
		const animDataAttr = wrapperEl.dataset.souvikWsAnim;
		if ( animDataAttr ) {
			try {
				const animConfig = JSON.parse( animDataAttr );
				new AnimationManager().init( wrapperEl, animConfig );
			} catch ( e ) {
				console.error( "Souvik WS Showcase - AnimationManager error:", e );
			}
		}

		// Coordinated controller resolves search, filter and pagination states
		try {
			new TeamShowcaseController( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - TeamShowcaseController error:", e );
		}

		try {
			new LazyLoader( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - LazyLoader error:", e );
		}

		// Sticky filter bar (only activates when data-filter-sticky="1")
		try {
			new StickyFilterManager( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - StickyFilterManager error:", e );
		}

		try {
			new PopupManager( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - PopupManager error:", e );
		}

		try {
			new DarkToggleEngine( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - DarkToggleEngine error:", e );
		}

		// Remove skeleton once real grid is present
		try {
			removeSkeleton( wrapperEl );
		} catch ( e ) {
			console.error( "Souvik WS Showcase - removeSkeleton error:", e );
		}
	};

	/* =========================================================================
	   DOM READY & CORE GLOBAL EXECUTION
	   ========================================================================= */

	// Proactive Page-Wide Dark Mode initialization BEFORE Dom Ready to prevent white flashes
	const savedDarkState = localStorage.getItem( 'souvik-ws-dark' );
	if ( savedDarkState === '1' ) {
		document.body.setAttribute( 'data-souvik-ws-dark', '1' );
	} else if ( savedDarkState === '0' ) {
		document.body.setAttribute( 'data-souvik-ws-dark', '0' );
	} else {
		// Respect wrapper's initial dark attribute if no localStorage is set
		const initialWrap = document.querySelector( '.souvik-ws-team-wrapper[data-dark="1"]' );
		if ( initialWrap ) {
			document.body.setAttribute( 'data-souvik-ws-dark', '1' );
		}
	}

	const run = () => {
		document
			.querySelectorAll( '.souvik-ws-team-wrapper' )
			.forEach( initWrapper );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}

	// Support Elementor editor live preview updates
	window.addEventListener( 'elementor/frontend/init', () => {
		if ( typeof elementorFrontend === 'undefined' ) return;

		elementorFrontend.hooks.addAction( 'frontend/element_ready/souvik-ws-team-showcase.default', ( $scope ) => {
			const wrapper = $scope[ 0 ]?.querySelector( '.souvik-ws-team-wrapper' );
			if ( wrapper ) initWrapper( wrapper );
		} );
	} );

	// Expose namespace for external use
	window.SouvikWSTeam = { AnimationManager, TeamShowcaseController, PopupManager, StickyFilterManager };

} )();
