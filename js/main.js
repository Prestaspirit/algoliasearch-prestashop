/**
 * Common variables and function for autocomplete and instant search
 */
var algolia_client = new AlgoliaSearch(algoliaSettings.app_id, algoliaSettings.search_key);
var custom_facets_types = algoliaSettings.theme.facet_types;

window.indicesCompare = function (a, b) {
    if (a.order1 < b.order1)
        return -1;

    if (a.order1 == b.order1 && a.order2 <= b.order2)
        return -1;

    return 1;
};

window.facetsCompare = function (a, b) {
    if (a.order < b.order)
        return -1;

    if (a.order == b.order)
        return -1;

    return 1;
};

/**
 * Autocomplete functions
 */

if (algoliaSettings.type_of_search == "autocomplete")
{
    window.getBrandingHits = function () {
        return function findMatches(q, cb) {
            return cb(["algolia-branding"]);
        }
    };
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

                this.query = "";

                var $this = this;

                this.helper = undefined;

                this.setHelper = function (helper) {
                    this.helper = helper;
                };

                this.updateUrl = function (push_state)
                {
                    var refinements = [];

                    /** Get refinements for conjunctive facets **/
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

                    /** Get refinements for disjunctive facets **/
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

                    var url = '#q=' + encodeURIComponent(this.query) + '&page=' + this.helper.page + '&refinements=' + encodeURIComponent(JSON.stringify(refinements)) + '&numerics_refinements=' + encodeURIComponent(JSON.stringify(this.helper.numericsRefinements)) + '&index_name=' + encodeURIComponent(JSON.stringify(this.helper.getIndex()));

                    /** If push_state is false wait for one second to push the state in history **/
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

                this.getRefinementsFromUrl = function(searchCallback)
                {
                    if (location.hash && location.hash.indexOf('#q=') === 0)
                    {
                        var params                          = location.hash.substring(3);
                        var pageParamOffset                 = params.indexOf('&page=');
                        var refinementsParamOffset          = params.indexOf('&refinements=');
                        var numericsRefinementsParamOffset  = params.indexOf('&numerics_refinements=');
                        var indexNameOffset                 = params.indexOf('&index_name=');

                        var q                               = decodeURIComponent(params.substring(0, pageParamOffset));
                        var page                            = parseInt(params.substring(pageParamOffset + '&page='.length, refinementsParamOffset));
                        var refinements                     = JSON.parse(decodeURIComponent(params.substring(refinementsParamOffset + '&refinements='.length, numericsRefinementsParamOffset)));
                        var numericsRefinements             = JSON.parse(decodeURIComponent(params.substring(numericsRefinementsParamOffset + '&numerics_refinements='.length, indexNameOffset)));
                        var indexName                       = JSON.parse(decodeURIComponent(params.substring(indexNameOffset + '&index_name='.length)));

                        this.query = q;

                        this.helper.clearRefinements();

                        /** Set refinements from url data **/
                        for (var i = 0; i < refinements.length; ++i) {
                            for (var refine in refinements[i]) {
                                this.helper.toggleRefine(refine, refinements[i][refine]);
                            }
                        }

                        this.helper.numericsRefinements = numericsRefinements;

                        this.helper.setPage(page);
                        this.helper.setIndex(indexName);

                        $(algoliaSettings.search_input_selector).val(this.query);

                        this.helper.search(this.query, searchCallback);

                    }
                };

                this.getFacets = function (content) {

                    var facets = [];

                    for (var i = 0; i < algoliaSettings.facets.length; i++)
                    {
                        var sub_facets = [];

                        if (custom_facets_types[algoliaSettings.facets[i].type] != undefined)
                        {
                            try
                            {
                                var params = custom_facets_types[algoliaSettings.facets[i].type]($this, content, algoliaSettings.facets[i]);

                                if (params)
                                    for (var k = 0; k < params.length; k++)
                                        sub_facets.push(params[k]);
                            }
                            catch(error)
                            {
                                console.log(error.message);
                                throw("Bad facet function for '" + algoliaSettings.facets[i].type + "'");
                            }
                        }
                        else
                        {
                            var content_facets = content.facets;

                            if (algoliaSettings.facets[i].type == 'disjunctive')
                                content_facets = content.disjunctiveFacets;

                            for (var key in content_facets[algoliaSettings.facets[i].tax])
                            {
                                var checked = $this.helper.isRefined(algoliaSettings.facets[i].tax, key);

                                var name = algoliaSettings.facetsLabels[key] != undefined ? algoliaSettings.facetsLabels[key] : key;
                                var nameattr = key;

                                var params = {
                                    type: {},
                                    checked: checked,
                                    nameattr: nameattr,
                                    name: name,
                                    count: content_facets[algoliaSettings.facets[i].tax][key]
                                };
                                params.type[algoliaSettings.facets[i].type] = true;

                                sub_facets.push(params);
                            }
                        }
                        facets.push({count: sub_facets.length, tax: algoliaSettings.facets[i].tax, facet_categorie_name: algoliaSettings.facets[i].name, sub_facets: sub_facets });
                    }

                    return facets;
                };

                this.getPages = function (content) {
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

                    return pages;
                };


                /**
                 * Rendering Html Function
                 */
                this.getHtmlForPagination = function (paginationTemplate, content, pages, facets) {
                    var pagination_html = paginationTemplate.render({
                        pages: pages,
                        facets_count: facets.length,
                        prev_page: (content.page > 0 ? content.page : false),
                        next_page: (content.page + 1 < content.nbPages ? content.page + 2 : false)
                    });

                    return pagination_html;
                };

                this.getHtmlForResults = function (resultsTemplate, content, facets) {

                    var results_html = resultsTemplate.render({
                        facets_count: facets.length,
                        getDate: this.getDate,
                        relevance_index_name: algoliaSettings.index_name + 'all',
                        sorting_indices: algoliaSettings.sorting_indices,
                        sortSelected: this.sortSelected,
                        hits: content.hits,
                        nbHits: content.nbHits,
                        nbHits_zero: (content.nbHits === 0),
                        nbHits_one: (content.nbHits === 1),
                        nbHits_many: (content.nbHits > 1),
                        query: $this.query,
                        processingTimeMS: content.processingTimeMS
                    });

                    return results_html;
                };

                this.getHtmlForFacets = function (facetsTemplate, facets) {

                    var facets_html = facetsTemplate.render({
                        facets: facets,
                        count: facets.length,
                        getDate: this.getDate,
                        relevance_index_name: algoliaSettings.index_name + 'all',
                        sorting_indices: algoliaSettings.sorting_indices,
                        sortSelected: this.sortSelected
                    });

                    return facets_html;
                };

                /**
                 * Helper methods
                 */
                this.sortSelected = function () {
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
                };

                this.gotoPage = function(page) {
                    this.helper.gotoPage(+page - 1);
                };

                this.getDate = function () {
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
            };
        }
        else
        {
            throw '[Algolia] Invalid instant-search selector: ' + algoliaSettings.instant_jquery_selector;
        }
    });
}