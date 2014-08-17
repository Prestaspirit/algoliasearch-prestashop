$(document).ready(function() {
	var algolia = new AlgoliaSearch(algolia_application_id, algolia_search_only_api_key);
	var index = algolia.initIndex(algolia_index_name);
	
	var template = Hogan.compile('<div class="hit">' +
	  '<div class="name">' +
	  	'<img src="//{{{ image_link }}}" class="algolia-search-image" />' +
	    '{{{ _highlightResult.name.value }}} ' +
	    '({{{ _highlightResult.category.value }}})' +
	  '</div>' +
	  '{{#matchingAttributes}}' +
	    '<div class="attribute"><b>{{ attribute }}</b>: {{{ value }}}</div>' +
	  '{{/matchingAttributes}}' +
	  '</div>');
	
	$('#algolia_search_query_top').typeahead(null, {
	  source: index.ttAdapter({ hitsPerPage: 5 }),
	  displayKey: 'email',
	  templates: {
	    suggestion: function(hit) {
	      hit.matchingAttributes = [];
	      for (var attribute in hit._highlightResult) {
	        if (attribute === 'name' || attribute == 'category') {
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