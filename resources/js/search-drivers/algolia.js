(function() {
    window.InkstoneSearchDriver = {
        name: 'algolia',
        client: null,
        async init(index, config, utils) {
            this.index = index;
            this.config = config;
            this.utils = utils;
            if (window.algoliasearch && config.app_id && config.api_key) {
                this.client = algoliasearch(config.app_id, config.api_key);
            }
        },
        async search(query) {
            if (!this.client || !this.config.index_name) return [];
            try {
                const searchIndex = this.client.initIndex(this.config.index_name);
                const { hits } = await searchIndex.search(query);
                return hits.map((hit) => {
                    const entry = this.index.find((e) => e.url === hit.url) || hit;
                    return { entry, score: 100 };
                });
            } catch (e) {
                return [];
            }
        },
        preview(entry, query) {
            return this.utils.preview(entry, query);
        },
        highlight(text, query) {
            return this.utils.highlight(text, query);
        }
    };
})();
