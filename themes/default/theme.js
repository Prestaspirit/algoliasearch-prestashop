jQuery(document).ready(function ($) {

    window.traductions = {
        'price': {
            'fr': 'Prix',
            'en': 'Price'
        },
        'categories': {
            'fr': 'Categories',
            'en': 'Categories'
        },
        'products': {
            'fr': 'Produits',
            'en': 'Products'
        },
        'price_asc': {
            'fr': 'Moins cher',
            'en': 'Lowest price first'
        }
    };

    if (algoliaSettings.type_of_search == "autocomplete")
    {
        var $autocompleteTemplate = Hogan.compile($('#autocomplete-template').text());

        var hogan_objs = [];
        algoliaSettings.indices.sort(indicesCompare);

        var indices = [];
        for (var i = 0; i < algoliaSettings.indices.length; i++)
            indices.push(algolia_client.initIndex(algoliaSettings.indices[i].index_name));

        for (var i = 0; i < algoliaSettings.indices.length; i++)
        {
            var category_title = traductions[algoliaSettings.indices[i].name] != undefined
                                    && traductions[algoliaSettings.indices[i].name][algoliaSettings.language] != undefined ?
                                    traductions[algoliaSettings.indices[i].name][algoliaSettings.language]
                                    : algoliaSettings.indices[i].name;
            hogan_objs.push({
                source: indices[i].ttAdapter({hitsPerPage: algoliaSettings.indices[i].nbHits}),
                displayKey: 'name',
                templates: {
                    header: '<div class="category">' + category_title + '</div>',
                    suggestion: function (hit) {
                        return $autocompleteTemplate.render(hit);
                    }
                }
            });

        }

        hogan_objs.push({
            source: getBrandingHits(),
            displayKey: 'name',
            templates: {
                suggestion: function (hit) {
                    return '<div class="footer">powered by <img width="45" src="' + algoliaSettings.plugin_url + '/img/algolia-logo.png"></div>';
                }
            }
        });

        $(algoliaSettings.search_input_selector).each(function (i) {
            $(this).typeahead({hint: false}, hogan_objs);

            $(this).on('typeahead:selected', function (e, item) {
                window.location.href = item.link ? item.link : item.url;
            });
        });
    }

    if (algoliaSettings.type_of_search == "instant")
    {

        for (var i = 0; i < algoliaSettings.sorting_indices.length; i++)
        {
            var label = window.traductions != undefined && window.traductions[algoliaSettings.sorting_indices[i].label] != undefined
            && window.traductions[algoliaSettings.sorting_indices[i].label][algoliaSettings.language] != undefined ?
                window.traductions[algoliaSettings.sorting_indices[i].label][algoliaSettings.language]
                : algoliaSettings.sorting_indices[i].label;

            algoliaSettings.sorting_indices[i].label = label;
        }

        /**
         * Variables Initialization
         */

        var old_content         = $(algoliaSettings.instant_jquery_selector).html();

        var resultsTemplate     = Hogan.compile($('#instant-content-template').text());
        var facetsTemplate      = Hogan.compile($('#instant-facets-template').text());
        var paginationTemplate  = Hogan.compile($('#instant-pagination-template').text());

        var conjunctive_facets  = [];
        var disjunctive_facets  = [];

        for (var i = 0; i < algoliaSettings.facets.length; i++)
        {
            if (algoliaSettings.facets[i].type == "conjunctive")
                conjunctive_facets.push(algoliaSettings.facets[i].tax);

            if (algoliaSettings.facets[i].type == "disjunctive")
                disjunctive_facets.push(algoliaSettings.facets[i].tax);

            if (algoliaSettings.facets[i].type == "slider")
                disjunctive_facets.push(algoliaSettings.facets[i].tax);

            if (algoliaSettings.facets[i].type == "menu")
                disjunctive_facets.push(algoliaSettings.facets[i].tax);
        }

        algoliaSettings.facets = algoliaSettings.facets.sort(facetsCompare);

        helper = algoliasearchHelper(algolia_client, algoliaSettings.index_name + 'all_' + algoliaSettings.language, {
            facets: conjunctive_facets,
            disjunctiveFacets: disjunctive_facets,
            hitsPerPage: algoliaSettings.number_by_page
        });

        engine.setHelper(helper);

        helper.on('result', searchCallback);

        /**
         * Functions
         */

        function performQueries(push_state)
        {
            engine.helper.search(engine.helper.state.query, searchCallback);

            engine.updateUrl(push_state);
        }

        function searchCallback(content)
        {
            var html_content = "";

            html_content += "<div id='algolia_instant_selector'>";

            var facets = [];
            var pages = [];

            if (content.hits.length > 0)
            {
                facets = engine.getFacets(content);
                pages = engine.getPages(content);

                html_content += engine.getHtmlForFacets(facetsTemplate, facets);
            }

            html_content += engine.getHtmlForResults(resultsTemplate, content, facets);

            if (content.hits.length > 0)
                html_content += engine.getHtmlForPagination(paginationTemplate, content, pages, facets);

            html_content += "</div>";

            $(algoliaSettings.instant_jquery_selector).html(html_content);

            updateSliderValues();
        }

        /**
         * Custom Facets Types
         */

        custom_facets_types["slider"] = function (engine, content, facet) {

            if (content.getFacetByName(facet.tax) != undefined)
            {
                var min = content.getFacetByName(facet.tax).stats.min;
                var max = content.getFacetByName(facet.tax).stats.max;

                var current_min = engine.helper.state.getNumericRefinement(facet.tax, ">=");
                var current_max = engine.helper.state.getNumericRefinement(facet.tax, "<=");

                if (current_min == undefined)
                    current_min = min;

                if (current_max == undefined)
                    current_max = max;

                var params = {
                    type: {},
                    current_min: Math.floor(current_min),
                    current_max: Math.ceil(current_max),
                    count: min == max ? 0 : 1,
                    min: Math.floor(min),
                    max: Math.ceil(max)
                };

                params.type[facet.type] = true;

                return [params];
            }

            return [];
        };

        custom_facets_types["menu"] = function (engine, content, facet) {

            var data = [];

            var all_count = 0;
            var all_unchecked = true;

            var content_facet = content.getFacetByName(facet.tax);

            for (var key in content_facet.data)
            {
                var checked = engine.helper.isRefined(facet.tax, key);

                all_unchecked = all_unchecked && !checked;

                var name = key;
                var nameattr = key;

                var params = {
                    type: {},
                    checked: checked,
                    nameattr: nameattr,
                    name: name,
                    print_count: true,
                    count: content_facet.data[key]
                };

                all_count += content_facet.data[key];

                params.type[facet.type] = true;

                data.push(params);
            }

            var params = {
                type: {},
                checked: all_unchecked,
                nameattr: 'all',
                name: 'All',
                print_count: false,
                count: all_count
            };

            params.type[facet.type] = true;

            data.unshift(params);

            return data;
        };

        /**
         * Bindings
         */

        $("body").on("click", ".sub_facet.menu", function (e) {

            e.stopImmediatePropagation();

            if ($(this).attr("data-name") == "all")
                engine.helper.state.clearRefinements($(this).attr("data-tax"));

            $(this).find("input[type='checkbox']").each(function (i) {
                $(this).prop("checked", !$(this).prop("checked"));

                if (false == engine.helper.isRefined($(this).attr("data-tax"), $(this).attr("data-name")))
                    engine.helper.state.clearRefinements($(this).attr("data-tax"));

                if ($(this).attr("data-name") != "all")
                    engine.helper.toggleRefine($(this).attr("data-tax"), $(this).attr("data-name"));
            });

            performQueries(true);
        });

        $("body").on("click", ".sub_facet", function () {

            $(this).find("input[type='checkbox']").each(function (i) {
                $(this).prop("checked", !$(this).prop("checked"));

                engine.helper.toggleRefine($(this).attr("data-tax"), $(this).attr("data-name"));
            });

            performQueries(true);
        });


        $("body").on("slide", "", function (event, ui) {
            updateSlideInfos(ui);
        });

        $("body").on("change", "#index_to_use", function () {
            engine.helper.setIndex($(this).val());

            engine.helper.setCurrentPage(0);

            performQueries(true);
        });

        $("body").on("slidechange", ".algolia-slider-true", function (event, ui) {

            var slide_dom = $(ui.handle).closest(".algolia-slider");
            var min = slide_dom.slider("values")[0];
            var max = slide_dom.slider("values")[1];

            if (parseInt(slide_dom.slider("values")[0]) >= parseInt(slide_dom.attr("data-min")))
                engine.helper.addNumericRefinement(slide_dom.attr("data-tax"), ">=", min);
            if (parseInt(slide_dom.slider("values")[1]) <= parseInt(slide_dom.attr("data-max")))
                engine.helper.addNumericRefinement(slide_dom.attr("data-tax"), "<=", max);

            if (parseInt(min) == parseInt(slide_dom.attr("data-min")))
                engine.helper.removeNumericRefinement(slide_dom.attr("data-tax"), ">=");

            if (parseInt(max) == parseInt(slide_dom.attr("data-max")))
                engine.helper.removeNumericRefinement(slide_dom.attr("data-tax"), "<=");

            updateSlideInfos(ui);
            performQueries(true);
        });

        $("body").on("click", ".algolia-pagination a", function (e) {
            e.preventDefault();

            engine.gotoPage($(this).attr("data-page"));
            performQueries(true);

            $("body").scrollTop(0);

            return false;
        });

        $(algoliaSettings.search_input_selector).keyup(function (e) {
            e.preventDefault();

            var $this = $(this);

            engine.helper.setQuery($(this).val());

            $(algoliaSettings.search_input_selector).each(function (i) {
                if ($(this)[0] != $this[0])
                    $(this).val(engine.helper.state.query);
            });

            if ($(this).val().length == 0) {

                clearTimeout(history_timeout);

                location.replace('#');

                $(algoliaSettings.instant_jquery_selector).html(old_content);

                return;
            }

            /* Uncomment to clear refinements on keyup */

            //engine.helper.clearRefinements();
            //engine.helper.clearNumericRefinements();


            performQueries(false);

            return false;
        });

        function updateSliderValues()
        {
            $(".algolia-slider-true").each(function (i) {
                var min = $(this).attr("data-min");
                var max = $(this).attr("data-max");

                var new_min = engine.helper.state.getNumericRefinement($(this).attr("data-tax"), ">=");
                var new_max = engine.helper.state.getNumericRefinement($(this).attr("data-tax"), "<=");

                if (new_min != undefined)
                    min = new_min;

                if (new_max != undefined)
                    max = new_max;

                $(this).slider({
                    min: parseInt($(this).attr("data-min")),
                    max: parseInt($(this).attr("data-max")),
                    range: true,
                    values: [min, max]
                });
            });
        };

        function updateSlideInfos(ui)
        {
            var infos = $(ui.handle).closest(".algolia-slider").nextAll(".algolia-slider-info");

            infos.find(".min").html(ui.values[0]);
            infos.find(".max").html(ui.values[1]);
        }

        /**
         * Initialization
         */

        $(algoliaSettings.search_input_selector).attr('autocomplete', 'off').attr('autocorrect', 'off').attr('spellcheck', 'false').attr('autocapitalize', 'off');

        engine.getRefinementsFromUrl(searchCallback);

        window.addEventListener("popstate", function(e) {
            engine.getRefinementsFromUrl(searchCallback);
        });
    }
});