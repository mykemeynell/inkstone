(function() {
    window.InkstoneSearchDriver = {
        name: 'typesense',
        client: null,
        async init(index, config, utils) {
            this.index = index;
            this.config = config;
            this.utils = utils;
            if (window.Typesense && config.server && config.server.api_key) {
                this.client = new Typesense.Client(config.server);
            }
        },
        async search(query) {
            if (!this.client) return [];
            try {
                const searchResults = await this.client.collections(this.config.server.collection_name).documents().search({
                    q: query,
                    query_by: 'title,content,headings',
                });
                return searchResults.hits.map((hit) => {
                    const entry = this.index.find((e) => e.url === hit.document.url) || hit.document;
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
