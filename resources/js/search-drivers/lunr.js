(function() {
    window.InkstoneSearchDriver = {
        name: 'lunr',
        lunrIndex: null,
        async init(index, config, utils) {
            this.index = index;
            this.config = config;
            this.utils = utils;

            if (window.lunr) {
                this.lunrIndex = lunr(function () {
                    this.ref('url');
                    this.field('title');
                    this.field('content');
                    this.field('headings');

                    index.forEach(function (doc) {
                        this.add({
                            url: doc.url,
                            title: doc.title,
                            content: doc.content,
                            headings: doc.headings.map((h) => h.text).join(' ')
                        });
                    }, this);
                });
            }
        },
        async search(query) {
            if (!this.lunrIndex) return [];
            return this.lunrIndex.search(query).map((result) => {
                const entry = this.index.find((e) => e.url === result.ref);
                return entry ? { entry, score: result.score * 100 } : null;
            }).filter(Boolean);
        },
        preview(entry, query) {
            return this.utils.preview(entry, query);
        },
        highlight(text, query) {
            return this.utils.highlight(text, query);
        }
    };
})();
