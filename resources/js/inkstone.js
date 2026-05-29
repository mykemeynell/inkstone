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
    const search = document.querySelector('[data-inkstone-search]');
    const results = document.querySelector('[data-inkstone-search-results]');
    const backToTop = document.querySelector('[data-inkstone-back-to-top]');
    const modes = ['system', 'light', 'dark'];

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
        button.className = 'inkstone-copy-button';
        button.setAttribute('aria-label', 'Copy');
        button.setAttribute('title', 'Copy');
        setIcon(button, 'copy');
        button.addEventListener('click', async () => {
            await copyText(code ? code.textContent || '' : pre.textContent || '');
            flashCopied(button);
        });
        frame.appendChild(button);
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

    let index = [];

    fetch(search.dataset.inkstoneSearchIndex || 'search-index.json')
        .then((response) => response.ok ? response.json() : [])
        .then((entries) => { index = Array.isArray(entries) ? entries : []; })
        .catch(() => { index = []; });

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9\s-]/g, ' ').replace(/\s+/g, ' ').trim();
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
        const terms = normalize(query).split(' ').filter((term) => term.length > 1);

        terms.forEach((term) => {
            const pattern = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'ig');
            html = html.replace(pattern, '<mark>$1</mark>');
        });

        return html;
    }

    function previewFor(entry) {
        const preview = entry.excerpt || entry.content || '';

        return preview.length > 220 ? `${preview.slice(0, 220)}...` : preview;
    }

    function hideResults() {
        results.hidden = true;
        results.innerHTML = '';
    }

    search.addEventListener('input', () => {
        const query = search.value.trim();
        results.innerHTML = '';

        if (query.length < 2) {
            hideResults();
            return;
        }

        const matches = index
            .map((entry) => ({ entry, score: scoreEntry(entry, query) }))
            .filter((match) => match.score > 0)
            .sort((left, right) => right.score - left.score)
            .slice(0, 8);

        if (matches.length === 0) {
            results.innerHTML = '<div class="inkstone-search-empty">No results found.</div>';
            results.hidden = false;
            return;
        }

        matches.forEach(({ entry }) => {
            const link = document.createElement('a');
            link.className = 'inkstone-search-result';
            link.href = entry.url;
            link.innerHTML = `<strong>${highlighted(entry.title, query)}</strong><span>${highlighted(previewFor(entry), query)}</span>`;
            results.appendChild(link);
        });

        results.hidden = false;
    });

    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            search.blur();
            hideResults();
        }
    });

    document.addEventListener('click', (event) => {
        if (!results.contains(event.target) && event.target !== search) {
            hideResults();
        }
    });
})();
