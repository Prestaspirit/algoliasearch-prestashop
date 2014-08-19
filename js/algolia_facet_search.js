$(document).ready(function() {
	var initial_dom = $("#columns");
	var refinements = {};
	var currentURL = window.location.href;
	var $inputfield = $("#algolia-search");
	var algolia = new AlgoliaSearch(algolia_application_id, algolia_search_only_api_key);
	var index = algolia.initIndex(algolia_index_name);
	
	var buildUrl = function(base, key, value) {
		var sep = (base.indexOf('?') > -1) ? '&' : '?';
		return base + sep + key + '=' + value;
	}
	
	if ($inputfield.val().length > 0) {
		search();
	}

	$inputfield.keyup(function() {
		if (typeof algolia_search_controller == 'undefined') {
			setState();
		}
		search();
	}).focus();

	window.toggleRefine = function(refinement) {
		refinements[refinement] = !refinements[refinement];
		search();
	}

	function setState() {
		var url = currentURL;
		if ($inputfield.val().length > 0) {
			showHits();
			url = buildUrl(algolia_search_url, 'q', encodeURI($inputfield.val()));
		} else if ($('#algolia-hits').length > 0) {
			$('#algolia-hits').remove();
			initial_dom.show();
		}
		window.history.pushState("", "", url);
	}

	function showHits() {
		initial_dom.hide();
		if ($('#algolia-hits').length === 0) {
			initial_dom.after(
			'<div id="algolia-hits" class="container">' +
				'<div class="row">' +
					'<div class="column col-sm-3">' +
						'<div id="facets"></div>' +
					'</div>' +
					'<div class="column col-sm-9">' +
						'<ul class="product_list grid row" id="hits"></div>' +
					'</div>' +
				'</div>' +
			'</div>');
		}
	}

	function search() {
		var filters = [];
		for (var refinement in refinements) {
			if (refinements[refinement]) {
				filters.push(refinement);
			}
		}
		index.search($inputfield.val(), searchCallback, { facets: '*', facetFilters: filters });
	}

	function searchCallback(success, content) {
		if (content.query != $inputfield.val()) {
			return;
		}
		if (content.hits.length == 0 || content.query.trim() === '') {
			$('#hits, #facets').empty();
			return;
		}

		var hits = '';
		for (var i = 0; i < content.hits.length; ++i) {
			var hit = content.hits[i];
			hits += formatProductDetails(hit);
		}
		$('#hits').html(hits);

		var facets = '';
		for (var facet in content.facets) {
			facets += '<h4>' + facet.charAt(0).toUpperCase() + facet.slice(1) + '</h4>';
			facets += '<ul>';
			var values = content.facets[facet];
			for (var value in values) {
				var refinement = facet + ':' + value;
				facets += '<li class="' + (refinements[refinement] ? 'refined' : '') + '">' +
				'<a href="#" onclick="toggleRefine(\'' + refinement + '\'); return false">' + value + '</a> (' + values[value] + ')' +
				'</li>';
			}
			facets += '</ul>';
		}
		$('#facets').html(facets);
	}
});

function formatProductDetails(hit) {
	return '<li class="ajax_block_product col-xs-12 col-sm-6 col-md-4 first-in-line first-item-of-tablet-line first-item-of-mobile-line">\
		<div class="product-container" itemscope="" itemtype="http://schema.org/Product">\
			<div class="product-image-container">\
				<a class="product_img_link" href="//' + hit.url + '" title="' + hit.name + '" itemprop="url">\
					<img class="replace-2x img-responsive" src="//' + hit.image_link_large + '" alt="' + hit.name + '" title="' + hit.name + '" width="250" height="250" itemprop="image">\
				</a>\
			</div>\
			<div class="right-block">\
				<h5 itemprop="name"><a class="product-name" href="//' + hit.url + '" title="' + hit.name + '" itemprop="url">' + hit.name + '</a></h5>\
				<div itemprop="offers" itemscope="" itemtype="http://schema.org/Offer" class="content_price">\
					<span itemprop="price" class="price product-price">' + hit.price.toFixed(2) + ' &euro;</span>\
					<meta itemprop="priceCurrency" content="EUR">\
				</div>\
				<div class="button-container">\
					<a class="button ajax_add_to_cart_button btn btn-default" href="http://prestashop.dev/en/cart?add=1&amp;id_product=1&amp;token=c04a62c7b2666757f6907cb217934d2a" rel="nofollow" title="Add to cart" data-id-product="1"><span>Add to cart</span></a>\
					<a itemprop="url" class="button lnk_view btn btn-default" href="//' + hit.url + '" title="View"><span>More</span></a>\
				</div>\
			</div>\
			<div class="functional-buttons clearfix"></div>\
		</div>\
	</li>';
}
