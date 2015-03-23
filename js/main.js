/**
 * Common stuff
 */
var algolia_client = new AlgoliaSearch(algoliaSettings.app_id, algoliaSettings.search_key);
var indexes = [];

for (var i = 0; i < algoliaSettings.indexes.length; i++)
    indexes.push(algolia_client.initIndex(algoliaSettings.indexes[i].index_name));

var myCompare = function (a, b) {
    if (a.order1 < b.order1)
        return -1;

    if (a.order1 == b.order1 && a.order2 <= b.order2)
        return -1;

    return 1;
}

/**
 * Autocomplete
 */

if (algoliaSettings.type_of_search == "autocomplete")
{
    jQuery(document).ready(function ($)
    {
        $(algoliaSettings.search_input_selector).keyup(function (e) {
            e.preventDefault();

            return false;
        });

        var $autocompleteTemplate = Hogan.compile($('#autocomplete-template').text());

        hogan_objs = [];

        algoliaSettings.indexes.sort(myCompare);

        for (var i = 0; i < algoliaSettings.indexes.length; i++)
        {
            hogan_objs.push({
                source: indexes[i].ttAdapter({hitsPerPage: algoliaSettings.number_by_type}),
                displayKey: 'title',
                templates: {
                    header: '<div class="category">' + algoliaSettings.indexes[i].name + '</div>',
                    suggestion: function (hit) {
                        return $autocompleteTemplate.render(hit);
                    }
                }
            });

        }

        function matcher () {
            return function findMatches(q, cb) {
                return cb(["algolia-branding"]);
            }
        }

        hogan_objs.push({
            source: matcher(),
            displayKey: 'title',
            templates: {
                suggestion: function (hit) {
                    return '<div class="footer">powered by <img width="50" src="' + algoliaSettings.plugin_url + '/img/algolia.png"></div>';
                }
            }
        });

        $(algoliaSettings.search_input_selector).each(function (i) {
            $(this).typeahead({hint: false}, hogan_objs);

            $(this).on('typeahead:selected', function (e, item) {
                window.location.href = item.permalink;
            });
        });
    });
}

/**
 * Instant Search
 */

if (algoliaSettings.type_of_search == "instant")
{
    var engine;
    var history_timeout;

    jQuery(document).ready(function ($) {

        if ($(algoliaSettings.instant_jquery_selector).length == 1)
        {

            engine = new function () {

                /**
                 * Variables Initialization
                 */

                this.old_content         = $(algoliaSettings.instant_jquery_selector).html();

                this.query               = "";

                this.template            = Hogan.compile($('#instant-content-template').text());
                this.facetsTemplate      = Hogan.compile($('#instant-facets-template').text());
                this.paginationTemplate  = Hogan.compile($('#instant-pagination-template').text());

                this.conjunctive_facets  = [];
                this.disjunctive_facets  = [];
                this.slider_facets       = [];

                var $this = this;

                for (var i = 0; i < algoliaSettings.facets.length; i++)
                {
                    if (algoliaSettings.facets[i].type == "conjunctive")
                        this.conjunctive_facets.push(algoliaSettings.facets[i].tax);

                    if (algoliaSettings.facets[i].type == "disjunctive")
                        this.disjunctive_facets.push(algoliaSettings.facets[i].tax);

                    if (algoliaSettings.facets[i].type == "slider")
                    {
                        this.disjunctive_facets.push(algoliaSettings.facets[i].tax);
                        this.slider_facets.push(algoliaSettings.facets[i].tax);
                    }
                }

                algoliaSettings.facets = algoliaSettings.facets.sort(myCompare);

                this.helper = new AlgoliaSearchHelper(algolia_client, algoliaSettings.index_name + 'all', {
                    facets: this.conjunctive_facets,
                    disjunctiveFacets: this.disjunctive_facets,
                    hitsPerPage: algoliaSettings.number_by_page
                });

                /**
                 * Functions
                 */
                this.updateUrl = function (push_state)
                {
                    var refinements = [];

                    for (var refine in this.helper.refinements)
                    {
                        if (this.helper.refinements[refine])
                        {
                            var i = refine.indexOf(':');
                            var r = {};

                            r[refine.slice(0, i)] = refine.slice(i + 1);

                            refinements.push(r);
                        }
                    }

                    for (var refine in this.helper.disjunctiveRefinements)
                    {
                        for (var value in this.helper.disjunctiveRefinements[refine])
                        {
                            if (this.helper.disjunctiveRefinements[refine][value])
                            {
                                var r = {};

                                r[refine] = value;

                                refinements.push(r);
                            }
                        }
                    }

                    var url = '#q=' + encodeURIComponent(this.query) + '&page=' + this.helper.page + '&refinements=' + encodeURIComponent(JSON.stringify(refinements)) + '&numerics_refinements=' + encodeURIComponent(JSON.stringify(this.helper.numericsRefinements)) + '&in=' + encodeURIComponent(JSON.stringify(this.helper.getIndex()));

                    if (push_state)
                        history.pushState(url, null, url);
                    else
                    {
                        clearTimeout(history_timeout);
                        history_timeout = setTimeout(function () {
                            history.pushState(url, null, url);
                        }, 1000);
                    }
                };

                this.getRefinementsFromUrl = function()
                {
                    if (location.hash && location.hash.indexOf('#q=') === 0)
                    {
                        var params                          = location.hash.substring(3);
                        var pageParamOffset                 = params.indexOf('&page=');
                        var refinementsParamOffset          = params.indexOf('&refinements=');
                        var numericsRefinementsParamOffset  = params.indexOf('&numerics_refinements=');
                        var indexNameOffset                 = params.indexOf('&in=');

                        var q                               = decodeURIComponent(params.substring(0, pageParamOffset));
                        var page                            = parseInt(params.substring(pageParamOffset + 6, refinementsParamOffset));
                        var refinements                     = JSON.parse(decodeURIComponent(params.substring(refinementsParamOffset + 13, numericsRefinementsParamOffset)));
                        var numericsRefinements             = JSON.parse(decodeURIComponent(params.substring(numericsRefinementsParamOffset + 22, indexNameOffset)));
                        var indexName                       = JSON.parse(decodeURIComponent(params.substring(indexNameOffset + 4)));

                        this.query = q;

                        this.helper.clearRefinements();

                        for (var i = 0; i < refinements.length; ++i) {
                            for (var refine in refinements[i]) {
                                this.helper.toggleRefine(refine, refinements[i][refine]);
                            }
                        }

                        this.helper.numericsRefinements = numericsRefinements;

                        this.helper.setPage(page);
                        this.helper.setIndex(indexName);

                        $(algoliaSettings.search_input_selector).val(this.query);

                        this.helper.search(this.query, this.searchCallback);

                    }
                };

                this.performQueries = function (push_state)
                {
                    this.helper.search(this.query, this.searchCallback);

                    this.updateUrl(push_state);

                };

                this.searchCallback = function(success, content) {

                    if (success)
                    {
                        var html_content = "";

                        html_content += "<div id='algolia_instant_selector'>";

                        var facets = [];

                        if (content.hits.length > 0)
                        {

                            /**
                             * Handle Facets
                             */
                            for (var i = 0; i < algoliaSettings.facets.length; i++)
                            {
                                var sub_facets = [];

                                if (algoliaSettings.facets[i].type == "conjunctive")
                                {
                                    for (var key in content.facets[algoliaSettings.facets[i].tax])
                                    {
                                        var checked = $this.helper.isRefined(algoliaSettings.facets[i].tax, key);

                                        var name = algoliaSettings.facetsLabels[key] != undefined ? algoliaSettings.facetsLabels[key] : key;

                                        sub_facets.push({
                                            conjunctive: 1,
                                            disjunctive: 0,
                                            slider: 0,
                                            checked: checked,
                                            name: name,
                                            count: content.facets[algoliaSettings.facets[i].tax][key]
                                        });
                                    }
                                }

                                if (algoliaSettings.facets[i].type == "slider")
                                {
                                    if (content.facets_stats[algoliaSettings.facets[i].tax] != undefined)
                                    {
                                        var min = content.facets_stats[algoliaSettings.facets[i].tax].min;
                                        var max = content.facets_stats[algoliaSettings.facets[i].tax].max;

                                        var current_min = $this.helper.getNumericsRefine(algoliaSettings.facets[i].tax, ">=");
                                        var current_max = $this.helper.getNumericsRefine(algoliaSettings.facets[i].tax, "<=");

                                        if (current_min == undefined)
                                            current_min = min;

                                        if (current_max == undefined)
                                            current_max = max;

                                        var name = algoliaSettings.facetsLabels[key] != undefined ? algoliaSettings.facetsLabels[key] : key;

                                        sub_facets.push({ current_min: current_min, current_max: current_max, count: min == max ? 0 : 1,slider: 1, conjunctive: 0, disjunctive: 0, name: name, min: min, max: max });
                                    }
                                }

                                if (algoliaSettings.facets[i].type == "disjunctive")
                                {
                                    for (var key in content.disjunctiveFacets[algoliaSettings.facets[i].tax])
                                    {
                                        var checked = $this.helper.isRefined(algoliaSettings.facets[i].tax, key);
                                        var name = algoliaSettings.facetsLabels[key] != undefined ? algoliaSettings.facetsLabels[key] : key;

                                        sub_facets.push({ slider: 0, conjunctive: 0, disjunctive: 1, checked: checked, name: name, count: content.disjunctiveFacets[algoliaSettings.facets[i].tax][key] });
                                    }
                                }

                                facets.push({count: sub_facets.length, tax: algoliaSettings.facets[i].tax, facet_categorie_name: algoliaSettings.facets[i].name, sub_facets: sub_facets });
                            }

                            /**
                             * Handle Pagination
                             */
                            var pages = [];
                            if (content.page > 5)
                            {
                                pages.push({ current: false, number: 1 });
                                pages.push({ current: false, number: '...', disabled: true });
                            }

                            for (var p = content.page - 5; p < content.page + 5; ++p)
                            {
                                if (p < 0 || p >= content.nbPages)
                                    continue;

                                pages.push({ current: content.page == p, number: (p + 1) });
                            }
                            if (content.page + 5 < content.nbPages)
                            {
                                pages.push({ current: false, number: '...', disabled: true });
                                pages.push({ current: false, number: content.nbPages });
                            }

                            /** Render Facet **/
                            html_content += $this.facetsTemplate.render({ facets: facets, count: facets.length });
                        }

                        /** Render results **/
                        html_content += $this.template.render({
                            facets_count: facets.length,
                            getDate: getDate,
                            sortSelected: sortSelected,
                            relevance_index_name: algoliaSettings.index_name + 'all',
                            sorting_indexes: algoliaSettings.sorting_indexes,
                            hits: content.hits,
                            nbHits: content.nbHits,
                            nbHits_zero: (content.nbHits === 0),
                            nbHits_one: (content.nbHits === 1),
                            nbHits_many: (content.nbHits > 1),
                            query: $this.query,
                            processingTimeMS: content.processingTimeMS
                        });

                        /** Render pagination **/
                        if (content.hits.length > 0)
                            html_content += $this.paginationTemplate.render({ pages: pages, prev_page: (content.page > 0 ? content.page : false), next_page: (content.page + 1 < content.nbPages ? content.page + 2 : false) });

                        html_content += "</div>";

                        $(algoliaSettings.instant_jquery_selector).html(html_content);


                        /** Call this function that should be define in the template **/
                        finishRenderingResults();
                    }
                };

                window.sortSelected = function () {
                    return function (val) {
                        var template = Hogan.compile(val);

                        var renderer = function(context) {
                            return function(text) {
                                return template.c.compile(text, template.options).render(context);
                            };
                        };

                        var render = renderer(this);

                        var index_name = render(val);

                        if (index_name == engine.helper.getIndex())
                            return "selected";
                        return "";
                    }
                }

                window.gotoPage = function(page) {
                    engine.helper.gotoPage(+page - 1);
                };

                window.getDate = function () {
                    return function (val) {
                        var template = Hogan.compile(val);

                        var renderer = function(context) {
                            return function(text) {
                                return template.c.compile(text, template.options).render(context);
                            };
                        };

                        var render = renderer(this);

                        var timestamp = render(val);


                        var date = new Date(timestamp * 1000);

                        var datevalues = [
                            date.getFullYear(),
                            date.getMonth()+1,
                            date.getDate(),
                            date.getHours(),
                            date.getMinutes(),
                            date.getSeconds(),
                        ];

                        var days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

                        var day = date.getDate();

                        if (day == 1)
                            day += "st";
                        else if (day == 2)
                            day += "nd";
                        else if (day == 3)
                            day += "rd";
                        else
                            day += "th";

                        return days[date.getDay()] + ", " + months[date.getMonth()] + " " + day + ", " + date.getFullYear();
                    }
                };

                window.addEventListener("popstate", function(e) {
                    engine.getRefinementsFromUrl();
                });

                /**
                 * Initialization
                 */

                $(algoliaSettings.search_input_selector).attr('autocomplete', 'off');
                this.getRefinementsFromUrl();
            }
        }
        else
        {
            throw '[Algolia] Invalid instant-search selector: ' + algoliaSettings.instant_jquery_selector;
        }
    });
}