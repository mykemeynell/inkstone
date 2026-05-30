(function () {
    const icons = {
        copy: '<svg aria-hidden="true" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
        check: '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>',
        moon: '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a7 7 0 1 0 11 11Z"></path></svg>',
        sun: '<svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>',
        system: '<svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M12 16v4"></path></svg>',
        up: '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"></path></svg>',
    };

    const toggle = document.querySelector('[data-inkstone-theme-toggle]');
    const themeIcon = document.querySelector('[data-inkstone-theme-icon]');
    const navToggle = document.querySelector('[data-inkstone-nav-toggle]');
    const sidebar = document.querySelector('[data-inkstone-sidebar]');
    const searchOpen = document.querySelector('[data-inkstone-search-open]');
    const searchClose = document.querySelector('[data-inkstone-search-close]');
    const searchOverlay = document.querySelector('[data-inkstone-search-overlay]');
    const search = document.querySelector('[data-inkstone-search]');
    const results = document.querySelector('[data-inkstone-search-results]');
    const backToTop = document.querySelector('[data-inkstone-back-to-top]');
    const modes = ['system', 'light', 'dark'];

    if (navigator.platform.includes('Win')) {
        document.querySelectorAll('.inkstone-keyboard-shortcut').forEach((el) => {
            el.textContent = el.textContent.replace('⌘', 'Ctrl+');
        });
    }

    function setIcon(element, name) {
        if (element) {
            element.innerHTML = icons[name] || '';
        }
    }

    function applyTheme(mode) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = mode === 'dark' || (mode === 'system' && prefersDark);

        document.documentElement.dataset.theme = mode;
        document.documentElement.classList.toggle('dark', dark);
        setIcon(themeIcon, mode === 'dark' ? 'moon' : mode === 'light' ? 'sun' : 'system');

        if (toggle) {
            toggle.setAttribute('aria-label', `Color theme: ${mode}`);
            toggle.setAttribute('title', `Color theme: ${mode}`);
            toggle.classList.add('is-switching');
            window.setTimeout(() => toggle.classList.remove('is-switching'), 180);
        }
    }

    if (toggle) {
        let mode = localStorage.getItem('inkstone-theme') || document.documentElement.dataset.theme || 'system';
        applyTheme(mode);

        toggle.addEventListener('click', () => {
            mode = modes[(modes.indexOf(mode) + 1) % modes.length];
            localStorage.setItem('inkstone-theme', mode);
            applyTheme(mode);
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if ((localStorage.getItem('inkstone-theme') || 'system') === 'system') {
                applyTheme('system');
            }
        });
    }

    if (navToggle && sidebar) {
        navToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => sidebar.classList.remove('is-open'));
        });
    }

    function saveNavState() {
        const state = {};
        document.querySelectorAll('.inkstone-nav-parent').forEach((el) => {
            const slug = el.dataset.slug;
            if (slug) {
                state[slug] = el.dataset.collapsed === 'true' ? 'true' : 'false';
            }
        });
        try {
            localStorage.setItem('inkstone-nav-state', JSON.stringify(state));
        } catch (e) { /* ignore */ }
    }

    function restoreNavState() {
        try {
            const saved = JSON.parse(localStorage.getItem('inkstone-nav-state'));
            if (!saved || typeof saved !== 'object') return;
            document.querySelectorAll('.inkstone-nav-parent').forEach((el) => {
                const slug = el.dataset.slug;
                if (slug && saved[slug] !== undefined) {
                    const collapsed = saved[slug] === 'true';
                    el.dataset.collapsed = collapsed ? 'true' : 'false';
                    el.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    const children = el.nextElementSibling;
                    if (children && children.classList.contains('inkstone-nav-children')) {
                        children.dataset.collapsed = collapsed ? 'true' : 'false';
                    }
                }
            });
        } catch (e) { /* ignore corrupted data */ }
    }

    if (sidebar) {
        sidebar.querySelectorAll('.inkstone-nav-parent').forEach((parent) => {
            parent.addEventListener('click', () => {
                const collapsed = parent.dataset.collapsed === 'true';
                parent.dataset.collapsed = collapsed ? 'false' : 'true';
                parent.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

                const children = parent.nextElementSibling;
                if (children && children.classList.contains('inkstone-nav-children')) {
                    children.dataset.collapsed = collapsed ? 'false' : 'true';
                }

                saveNavState();
            });

            parent.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    parent.click();
                }
            });
        });

        restoreNavState();
    }

    async function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
    }

    function flashCopied(button) {
        setIcon(button, 'check');
        button.classList.add('is-copied');
        window.setTimeout(() => {
            setIcon(button, 'copy');
            button.classList.remove('is-copied');
        }, 1200);
    }

    document.querySelectorAll('pre[data-copyable="true"]').forEach((pre) => {
        if (pre.querySelector('.inkstone-copy-button')) {
            return;
        }

        let frame = pre.parentElement;

        if (!frame || !frame.classList.contains('inkstone-code-frame')) {
            frame = document.createElement('div');
            frame.className = 'inkstone-code-frame';
            pre.parentNode.insertBefore(frame, pre);
            frame.appendChild(pre);
        }

        const button = document.createElement('button');
        const code = pre.querySelector('code');
        button.type = 'button';
        button.className = 'inkstone-copy-button is-subtle';
        button.setAttribute('aria-label', 'Copy');
        button.setAttribute('title', 'Copy');
        setIcon(button, 'copy');
        button.addEventListener('click', async () => {
            await copyText(code ? code.textContent || '' : pre.textContent || '');
            flashCopied(button);
        });
        frame.appendChild(button);
    });

    document.querySelectorAll('[data-inkstone-demo]').forEach((demo) => {
        const tabs = Array.from(demo.querySelectorAll('[data-inkstone-demo-tab]'));
        const panels = Array.from(demo.querySelectorAll('[data-inkstone-demo-panel]'));

        function showPanel(name) {
            tabs.forEach((tab) => {
                const active = tab.dataset.inkstoneDemoTab === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panels.forEach((panel) => {
                const active = panel.dataset.inkstoneDemoPanel === name;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => showPanel(tab.dataset.inkstoneDemoTab || 'source'));
        });
    });

    document.querySelectorAll('[data-inkstone-heading-copy]').forEach((anchor) => {
        setIcon(anchor, 'copy');
        anchor.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = `${window.location.origin}${window.location.pathname}${anchor.getAttribute('href')}`;
            await copyText(url);
            setIcon(anchor, 'check');
            anchor.classList.add('is-copied');
            window.setTimeout(() => {
                setIcon(anchor, 'copy');
                anchor.classList.remove('is-copied');
            }, 1200);
        });
    });

    const tocLinks = Array.from(document.querySelectorAll('[data-inkstone-toc-link]'));
    const headings = tocLinks
        .map((link) => document.getElementById(link.dataset.inkstoneTocLink || ''))
        .filter(Boolean);

    function setActiveToc(id) {
        tocLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.inkstoneTocLink === id);
        });
    }

    if (headings.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible[0] && visible[0].target.id) {
                setActiveToc(visible[0].target.id);
            }
        }, { rootMargin: '-96px 0px -60% 0px', threshold: [0, 1] });

        headings.forEach((heading) => observer.observe(heading));
    }

    if (backToTop) {
        setIcon(backToTop, 'up');

        window.addEventListener('scroll', () => {
            backToTop.classList.toggle('is-visible', window.scrollY > 480);
        }, { passive: true });

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (!search || !results) {
        return;
    }

    const driver = search.dataset.inkstoneSearchDriver || 'json';
    const config = JSON.parse(search.dataset.inkstoneSearchConfig || '{}');
    let index = [];

    const utils = {
        normalize,
        score: scoreEntry,
        highlight: highlighted,
        preview: previewFor,
        escapeHtml
    };

    if (searchOpen) {
        searchOpen.addEventListener('click', openSearch);
    }

    if (searchClose) {
        searchClose.addEventListener('click', closeSearch);
    }

    function openSearch() {
        if (searchOverlay) {
            searchOverlay.hidden = false;
        }

        window.setTimeout(() => search.focus(), 0);
    }

    function closeSearch() {
        hideResults();
        search.value = '';

        if (searchOverlay) {
            searchOverlay.hidden = true;
        }
    }

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openSearch();
        }
    });

    fetch(search.dataset.inkstoneSearchIndex || 'search-index.json')
        .then((response) => response.ok ? response.json() : [])
        .then(async (data) => {
            const entries = Array.isArray(data) ? data : (data.documents || []);

            index = entries.map((doc) => ({
                title: doc.title || '',
                url: doc.url || '',
                content: doc.content || doc.body || '',
                headings: Array.isArray(doc.headings) && typeof doc.headings[0] === 'string'
                    ? doc.headings.map((text) => ({ text }))
                    : (doc.headings || []),
                excerpt: doc.excerpt || doc.content || doc.body || '',
                section: doc.section || '',
            }));

            if (window.InkstoneSearchDriver && typeof window.InkstoneSearchDriver.init === 'function') {
                await window.InkstoneSearchDriver.init(index, config, utils);
            }
        })
        .catch(() => { index = []; });

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9._-]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character]));
    }

    function editDistance(left, right) {
        if (Math.abs(left.length - right.length) > 2) {
            return 99;
        }

        const costs = Array.from({ length: right.length + 1 }, (_, index) => index);

        for (let i = 1; i <= left.length; i += 1) {
            let previous = i;

            for (let j = 1; j <= right.length; j += 1) {
                const current = left[i - 1] === right[j - 1]
                    ? costs[j - 1]
                    : Math.min(costs[j - 1], previous, costs[j]) + 1;
                costs[j - 1] = previous;
                previous = current;
            }

            costs[right.length] = previous;
        }

        return costs[right.length];
    }

    function approximateScore(term, words) {
        let best = 0;

        words.slice(0, 300).forEach((word) => {
            if (word.length < 3 || Math.abs(word.length - term.length) > 2) {
                return;
            }

            const distance = editDistance(term, word);
            const ratio = distance / Math.max(term.length, word.length);

            if (ratio <= 0.35) {
                best = Math.max(best, 18 - (distance * 5));
            }
        });

        return best;
    }

    function scoreEntry(entry, query) {
        const title = normalize(entry.title);
        const excerpt = normalize(entry.excerpt);
        const content = normalize(entry.content);
        const headingText = normalize((entry.headings || []).map((heading) => heading.text).join(' '));
        const haystack = `${title} ${headingText} ${excerpt} ${content}`;
        const words = haystack.split(' ').filter(Boolean);
        const terms = normalize(query).split(' ').filter(Boolean);

        if (terms.length === 0) {
            return 0;
        }

        let score = title.includes(normalize(query)) ? 80 : 0;

        terms.forEach((term) => {
            if (title.includes(term)) {
                score += title.startsWith(term) ? 45 : 35;
            } else if (approximateScore(term, title.split(' ').filter(Boolean)) > 0) {
                score += approximateScore(term, title.split(' ').filter(Boolean)) * 4;
            } else if (headingText.includes(term)) {
                score += 28;
            } else if (excerpt.includes(term)) {
                score += 22;
            } else if (content.includes(term)) {
                score += 12;
            } else {
                score += approximateScore(term, words);
            }
        });

        return score;
    }

    function highlighted(value, query) {
        let html = escapeHtml(value);
        const normalizedQuery = normalize(query);
        const terms = normalizedQuery.split(' ').filter((term) => term.length > 1);

        const rawQuery = query.trim().toLowerCase();
        if (rawQuery.length > 1) {
            terms.unshift(rawQuery);
        }

        if (terms.length === 0) return html;

        // Sort terms by length descending to match longest possible terms first
        terms.sort((a, b) => b.length - a.length);

        const pattern = new RegExp(`(${terms.map((term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`, 'ig');

        return html.replace(pattern, '<mark>$1</mark>');
    }

    function previewFor(entry, query) {
        const content = entry.content || entry.excerpt || '';

        if (!query || !content) {
            return content.length > 220 ? `${content.slice(0, 220)}...` : content;
        }

        const normalizedContent = String(content).toLowerCase().replace(/[^a-z0-9._-]/g, ' ');
        const terms = normalize(query).split(' ').filter((term) => term.length > 1);

        const rawQuery = query.trim().toLowerCase();
        if (rawQuery.length > 1) {
            terms.unshift(rawQuery);
        }

        if (terms.length === 0) {
            return content.length > 220 ? `${content.slice(0, 220)}...` : content;
        }

        // Use the longest term as it is usually the most specific
        const sortedTerms = [...terms].sort((a, b) => b.length - a.length);
        let bestIndex = -1;

        for (const term of sortedTerms) {
            const pattern = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+'), 'i');
            const match = normalizedContent.match(pattern);
            if (match) {
                bestIndex = match.index;
                break;
            }
        }

        if (bestIndex === -1) {
            return content.length > 220 ? `${content.slice(0, 220)}...` : content;
        }

        // Create a snippet window around the best match
        let start = Math.max(0, bestIndex - 80);
        let end = Math.min(content.length, bestIndex + 140);

        // Adjust start to the beginning of a word if possible, without losing the match
        if (start > 0) {
            const spaceIndex = content.indexOf(' ', start);
            if (spaceIndex !== -1 && spaceIndex < bestIndex) {
                start = spaceIndex + 1;
            }
        }

        // Adjust end to the end of a word if possible
        if (end < content.length) {
            const spaceIndex = content.lastIndexOf(' ', end);
            if (spaceIndex !== -1 && spaceIndex > bestIndex) {
                end = spaceIndex;
            }
        }

        let snippet = content.slice(start, end).trim();

        if (start > 0) {
            snippet = '...' + snippet;
        }

        if (end < content.length) {
            snippet = snippet + '...';
        }

        return snippet;
    }

    function hideResults() {
        results.hidden = true;
        results.innerHTML = '';
    }

    function activeResult() {
        return results.querySelector('.inkstone-search-result.is-active');
    }

    function setActiveResult(next) {
        results.querySelectorAll('.inkstone-search-result').forEach((result) => {
            result.classList.toggle('is-active', result === next);
            result.setAttribute('aria-selected', result === next ? 'true' : 'false');
        });
    }

    function moveActiveResult(direction) {
        const items = Array.from(results.querySelectorAll('.inkstone-search-result'));

        if (items.length === 0) {
            return;
        }

        const current = activeResult();
        const currentIndex = current ? items.indexOf(current) : -1;
        const nextIndex = currentIndex === -1
            ? (direction > 0 ? 0 : items.length - 1)
            : (currentIndex + direction + items.length) % items.length;

        setActiveResult(items[nextIndex]);
        items[nextIndex].scrollIntoView({ block: 'nearest' });
    }

    search.addEventListener('input', async () => {
        const query = search.value.trim();
        results.innerHTML = '';
        search.setAttribute('aria-expanded', 'false');

        if (query.length < 2) {
            hideResults();
            return;
        }

        let matches = [];

        if (window.InkstoneSearchDriver && typeof window.InkstoneSearchDriver.search === 'function') {
            matches = await window.InkstoneSearchDriver.search(query, index, config, utils);
        }

        if (matches.length === 0) {
            matches = index
                .map((entry) => ({ entry, score: scoreEntry(entry, query) }))
                .filter((match) => match.score > 0)
                .sort((left, right) => right.score - left.score);
        }

        matches = matches.slice(0, 8);

        if (matches.length === 0) {
            results.innerHTML = '<div class="inkstone-search-empty">No results found.</div>';
            results.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            return;
        }

        matches.forEach(({ entry }) => {
            const link = document.createElement('a');
            link.className = 'inkstone-search-result';
            link.href = entry.url;
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');

            const driverPreview = (window.InkstoneSearchDriver && typeof window.InkstoneSearchDriver.preview === 'function')
                ? window.InkstoneSearchDriver.preview(entry, query, utils)
                : previewFor(entry, query);

            const driverHighlight = (window.InkstoneSearchDriver && typeof window.InkstoneSearchDriver.highlight === 'function')
                ? (text) => window.InkstoneSearchDriver.highlight(text, query, utils)
                : (text) => highlighted(text, query);

            const sectionHtml = entry.section ? `<span class="inkstone-search-result-section">${escapeHtml(entry.section)}</span>` : '';
            link.innerHTML = `${sectionHtml}<strong>${driverHighlight(entry.title)}</strong><span>${driverHighlight(driverPreview)}</span>`;
            results.appendChild(link);
        });

        results.setAttribute('role', 'listbox');
        results.hidden = false;
        search.setAttribute('aria-expanded', 'true');
    });

    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSearch();
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveActiveResult(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveActiveResult(-1);
        } else if (event.key === 'Enter' && activeResult()) {
            event.preventDefault();
            activeResult().click();
        }
    });

    document.addEventListener('click', (event) => {
        if (searchOverlay && event.target === searchOverlay) {
            closeSearch();
        }
    });
})();
