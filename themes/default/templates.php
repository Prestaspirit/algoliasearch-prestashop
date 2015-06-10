<script type="text/template" id="autocomplete-template">
    <div class="result">
        <div class="title">
            {{#image_link_small}}
            <div class="thumb">
                <img style="width: 30px" src="//{{{image_link_small}}}" />
            </div>
            {{/image_link_small}}
            <div class="info{{^image_link_small}}-without-thumb{{/image_link_small}}">
            {{#_highlightResult.path}}
                {{{_highlightResult.path.value}}} ({{product_count}})
            {{/_highlightResult.path}}
            {{^_highlightResult.path}}
                {{{ _highlightResult.name.value }}}
            {{/_highlightResult.path}}

            {{#price_tax_incl}}
                <div class="algoliasearch-autocomplete-price">{{price_tax_incl}}{{currency}}</div>
            {{/price_tax_incl}}
            {{#_highlightResult.category}}
                <div class="algoliasearch-autocomplete-price">{{{_highlightResult.category.value}}}</div>
            {{/_highlightResult.category}}
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</script>

<script type="text/template" id="instant-content-template">
    <div class="hits{{#facets_count}} with_facets{{/facets_count}}">
        {{#hits.length}}
        <div class="infos">
            <div style="float: left">
                {{nbHits}} result{{^nbHits_one}}s{{/nbHits_one}} {{#query}}found matching "<strong>{{query}}</strong>"{{/query}} in {{processingTimeMS}} ms
            </div>
            <div class="logo" style="float: right;">
                by <img src="<?php echo $path ?>/img/algolia-logo.png">
            </div>
            {{#sorting_indices.length}}
            <div style="float: right; margin-right: 10px;">
                Order by
                <select id="index_to_use">
                    <option {{#sortSelected}}{{relevance_index_name}}{{/sortSelected}} value="{{relevance_index_name}}">relevance</option>
                    {{#sorting_indices}}
                    <option {{#sortSelected}}{{index_name}}{{/sortSelected}} value="{{index_name}}">{{label}}</option>
                    {{/sorting_indices}}
                </select>
            </div>
            {{/sorting_indices.length}}
            <div style="clear: both;"></div>
        </div>
        {{/hits.length}}

        {{#hits}}
        <a href="{{permalink}}">
            <div class="result-wrapper">
                <div class="result">
                    <div class="result-content">
                        <div>
                            <h1 class="result-title">
                                {{{ _highlightResult.name.value }}}
                            </h1>
                        </div>
                        <div class="result-sub-content">
                            <div class="result-thumbnail">
                            {{#image_link_large}}
                                <img height="216" src="//{{{ image_link_large }}}" />
                            {{/image_link_large}}
                            {{^image_link_large}}
                            <div style="height: 216px;"></div>
                            {{/image_link_large}}
                            </div>
                            <div class="result-excerpt">
                                <div class="price">Price : {{price_tax_incl}}{{currency}}</div>
                                <div class="description">{{{description_short}}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        {{/hits}}
        {{^hits.length}}
        <div class="infos">
            No results found matching "<strong>{{query}}</strong>". <span class="clear">Clear query and filters</span>
        </div>
        {{/hits.length}}
        <div style="clear: both;"></div>
    </div>
</script>

<script type="text/template" id="instant-facets-template">
<div class="facets{{#count}} with_facets{{/count}}">
    {{#facets}}
    {{#count}}
    <div class="facet">
        <div class="name">
            {{ facet_categorie_name }}
        </div>
        <div>
            {{#sub_facets}}

                {{#type.menu}}
                <div data-tax="{{tax}}" data-name="{{nameattr}}" data-type="menu" class="{{#checked}}checked {{/checked}}sub_facet menu">
                    <input style="display: none;" data-tax="{{tax}}" {{#checked}}checked{{/checked}} data-name="{{nameattr}}" class="facet_value" type="checkbox" />
                    {{name}} {{#print_count}}({{count}}){{/print_count}}
                </div>
                {{/type.menu}}

                {{#type.conjunctive}}
                <div data-name="{{tax}}" data-type="conjunctive" class="{{#checked}}checked {{/checked}}sub_facet conjunctive">
                    <input style="display: none;" data-tax="{{tax}}" {{#checked}}checked{{/checked}} data-name="{{nameattr}}" class="facet_value" type="checkbox" />
                    {{name}} ({{count}})
                </div>
                {{/type.conjunctive}}

                {{#type.slider}}
                <div class="algolia-slider algolia-slider-true" data-tax="{{tax}}" data-min="{{min}}" data-max="{{max}}"></div>
                <div class="algolia-slider-info">
                    <div class="min" style="float: left;">{{current_min}}</div>
                    <div class="max" style="float: right;">{{current_max}}</div>
                    <div style="clear: both"></div>
                </div>
                {{/type.slider}}

                {{#type.disjunctive}}
                <div data-name="{{tax}}" data-type="disjunctive" class="{{#checked}}checked {{/checked}}sub_facet disjunctive">
                    <input data-tax="{{tax}}" {{#checked}}checked{{/checked}} data-name="{{nameattr}}" class="facet_value" type="checkbox" />
                    {{name}} ({{count}})
                </div>
                {{/type.disjunctive}}

            {{/sub_facets}}
        </div>
    </div>
    {{/count}}
    {{/facets}}
</div>
</script>

<script type="text/template" id="instant-pagination-template">
<div class="pagination-wrapper{{#facets_count}} with_facets{{/facets_count}}">
    <div class="text-center">
        <ul class="algolia-pagination">
            <a href="#" data-page="{{prev_page}}">
                <li {{^prev_page}}class="disabled"{{/prev_page}}>
                    &laquo;
                </li>
            </a>

            {{#pages}}
            <a href="#" data-page="{{number}}" return false;">
                <li class="{{#current}}active{{/current}}{{#disabled}}disabled{{/disabled}}">
                    {{ number }}
                </li>
            </a>
            {{/pages}}

            <a href="#" data-page="{{next_page}}">
                <li {{^next_page}}class="disabled"{{/next_page}}>
                    &raquo;
                </li>
            </a>
        </ul>
    </div>
</div>
</script>