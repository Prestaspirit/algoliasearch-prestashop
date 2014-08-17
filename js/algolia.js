$(document).ready(function() {
// Replace the following values by your ApplicationID and ApiKey.
var algolia = new AlgoliaSearch(algolia_application_id, algolia_search_only_api_key);
// replace YourIndexName by the name of the index you want to query.
var index = algolia.initIndex(algolia_index_name);

// Mustache templating by Hogan.js (http://mustache.github.io/)
var template = Hogan.compile('<div class="hit">' +
  '<div class="name">' +
    '{{{ _highlightResult.name.value }}} ' +
    '({{{ _highlightResult.category.value }}})' +
  '</div>' +
  '{{#matchingAttributes}}' +
    '<div class="attribute"><b>{{ attribute }}</b>: {{{ value }}}</div>' +
  '{{/matchingAttributes}}' +
  '</div>');

// typeahead.js initialization
$('#algolia_search_query_top').typeahead(null, {
  source: index.ttAdapter({ hitsPerPage: 5 }),
  displayKey: 'email',
  templates: {
    suggestion: function(hit) {
      // select matching attributes only
      hit.matchingAttributes = [];
      for (var attribute in hit._highlightResult) {
        if (attribute === 'name' || attribute == 'category') {
          // already handled by the template
          continue;
        }
        // all others attributes that are matching should be added in the matchingAttributes array
        // so we can display them in the dropdown menu. Non-matching attributes are skipped.
        if (hit._highlightResult[attribute].matchLevel !== 'none') {
          hit.matchingAttributes.push({ attribute: attribute, value: hit._highlightResult[attribute].value });
        }
      }

      // render the hit using Hogan.js
      return template.render(hit);
    }
  }
});
});