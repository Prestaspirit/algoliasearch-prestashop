$(document).ready(function() {
	var algolia = new AlgoliaSearch(algolia_application_id, algolia_search_only_api_key);
	var index = algolia.initIndex(algolia_index_name);

	var template = Hogan.compile('<div class="hit">' +
	'<a href="{{{ \'link_\'+algolia_search_iso_code }}}" class="algolia-search-result">' +
	'<img src="//{{{ [\'image_link_small_\'+algolia_search_iso_code] }}}" class="algolia-search-image img-circle" />' +
	'{{{ _highlightResult[\'name_\'+algolia_search_iso_code].value }}} ' +
	'({{{ _highlightResult[\'category_\'+algolia_search_iso_code].value }}})' +
	'</a>' +
	'{{#matchingAttributes}}' +
	'<div class="attribute"><b>{{ attribute }}</b>: {{{ value }}}</div>' +
	'{{/matchingAttributes}}' +
	'</div>');

	$('#algolia-search').typeahead(null, {
		source: index.ttAdapter({ hitsPerPage: 8 }),
		templates: {
			suggestion: function(hit) {
				hit.matchingAttributes = [];
				for (var attribute in hit._highlightResult) {
					if (attribute === 'name' || attribute === 'category') {
						continue;
					}
					if (hit._highlightResult[attribute].matchLevel !== 'none') {
						hit.matchingAttributes.push({ attribute: attribute, value: hit._highlightResult[attribute].value });
					}
				}
				return template.render(hit);
			}
		}
	});
});
