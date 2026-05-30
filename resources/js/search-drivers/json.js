(function() {
    window.InkstoneSearchDriver = {
        name: 'json',
        async init(index, config, utils) {
            this.index = index;
            this.config = config;
            this.utils = utils;
        },
        async search(query) {
            const terms = this.utils.normalize(query).split(' ').filter(t => t.length > 0);
            if (terms.length === 0) return [];
            return this.index.map(entry => {
                const score = this.utils.score(entry, terms);
                return score > 0 ? { entry, score } : null;
            }).filter(Boolean).sort((a, b) => b.score - a.score);
        },
        preview(entry, query) {
            return this.utils.preview(entry, query);
        },
        highlight(text, query) {
            return this.utils.highlight(text, query);
        }
    };
})();
