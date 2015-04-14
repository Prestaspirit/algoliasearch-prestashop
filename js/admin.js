var selectTab;

jQuery(document).ready(function($) {
    /**
     * Handle display/hide of subcontent
     */
    $(".has-extra-content input[type='radio']").each(function () {
        if ($(this).is(':checked'))
            $(this).closest(".has-extra-content").find(".show-hide").show();
    });

    $(".has-extra-content input[type='radio']").change(function (e) {
        $(".has-extra-content input[type='radio']").each(function () {
            if ($(this).is(':checked'))
                $(this).closest(".has-extra-content").find(".show-hide").show();
            else
                $(this).closest(".has-extra-content").find(".show-hide").hide();
        });
    });

    function handleScreenshot()
    {
        if ($('input[name="TYPE_OF_SEARCH"]:checked').val() == 'autocomplete')
        {
            $('.screenshot.autocomplete').show();
            $('.screenshot.instant').hide();
        }
        else
        {
            $('.screenshot.autocomplete').hide();
            $('.screenshot.instant').show();
        }
    }

    handleScreenshot();

    $(".has-extra-content input[type='radio']").change(function (e) {
        $(".has-extra-content input[type='radio']").each(function () {
            if ($(this).is(':checked'))
                $(this).closest(".has-extra-content").find(".show-hide").show();
            else
                $(this).closest(".has-extra-content").find(".show-hide").hide();
        });

        handleScreenshot();
    });


    /** Handle Tab **/
    if (location.hash !== '')
        $('a[href="' + location.hash + '"]').tab('show');

    $('a[data-toggle="tab"]').on('click', function(e) {
        location.hash = $(e.target).attr('href').substr(1);
    });

    /**
     * Handle Sub Tab
     */

    function reorderMetas()
    {
        $('#extra-metas tr').each(function (i) {
            if ($(this).find('td:first input[type="checkbox"]').prop('checked') || $(this).find('td:first i').length > 0)
            {
                $('#extra-meta-and-taxonomies').append($(this));
            }
        });

        $('#extra-meta-and-taxonomies tr').each(function (i) {
            if ($(this).find('td:first input[type="checkbox"]').prop('checked') == false && $(this).find('td:first i').length <= 0)
                $('#extra-metas-attributes table tr:first').after($(this));
        });
    }

    $('#extra-metas tr td:first-child input').click(function (e) {
        reorderMetas();
    });

    reorderMetas();

    $('#extra-metas-form').submit(function (e) {
        $('#extra-metas tr').each(function (i) {
            $(this).find('.order').val(i);
        });
    });

    /**
     * Handle disabling
     */

    function disableInput(div)
    {
        $(div + " input, " + div + " select").prop('disabled', false);
        $(div + " tr:not(:first)").each(function (i) {
            var tds = $(this).find("td");

            if ($(tds[0]).find('input[type="checkbox"]').prop('checked') == false)
            {
                $(this).find("td").find("input,select").slice(1).prop('disabled', true);
            }
        });
    }

    var disabelable = ['#indexable-types', '#taxonomies', '#extra-metas', '#indexable-types'];

    for (var i = 0; i < disabelable.length; i++)
    {
        (function (i) {
            disableInput(disabelable[i]);

            $(disabelable[i] + " input").click(function () {
                disableInput(disabelable[i]);
            });
        })(i);

    }

    /**
     * Handle Theme chooser
     */

    $('#algolia-settings .theme').click(function () {
        $('#algolia-settings .theme').removeClass('active');
        $(this).addClass('active');
    });

    /**
     * Handle Sorting
     */

    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    $('#extra-metas tr, #indexable-types tr, #custom-ranking tr, #searchable_attributes tr').sort(function (a, b) {
        var contentA = parseInt($(a).attr('data-order'));
        var contentB = parseInt($(b).attr('data-order'));

        return (contentA < contentB) ? -1 : (contentA > contentB) ? 1 : 0;
    }).each(function (_, container) {
        $(container).parent().append(container);
    });;

    $("#extra-metas tbody, #indexable-types tbody, #custom-ranking tbody, #searchable_attributes tbody").sortable({
        containment: "parent",
        items: 'tr:not(:first)',
        helper: fixHelper
    });

    /**
     * Handle Async Indexation
     */

    $(document).ready(function () {

        function renderPercentage(percent)
        {
            return "<div style='float: left; width: 300px; height: 20px; border: solid 1px #dddddd;'>" +
                "<div style='width: " + percent + "%; height: 20px; background-color: rgba(42, 148, 0, 0.6);'></div>" +
                "</div>" +
                "<div style='float: left; margin-left: 20px'>" + percent + "%</div>"
        }

        function render(actions, i)
        {
            var percentage = Math.ceil(i * 100 / actions.length);
            if (i == -1)
                percentage = 0;

            $("#reindex-percentage").html(renderPercentage(percentage));

            if (i == -1)
                return;

            $("#reindex-log").append(
                "<tr>" +
                "<td>" + actions[i].name + " " + actions[i].sup + "<td>" +
                "<td>[OK]</td>" +
                "</tr>");
        }

        $("body").on("click", ".close-results", function () {
            $("#results-wrapper").hide();
            $(this).hide();
            $("#algolia_reindex").show();
        });

        $("#algolia_reindex").click(function (e) {
            var base_url    = $(this).attr('data-formurl');
            var actions     = [];
            var batch_count = algoliaAdminSettings.batch_count;

            $("#results-wrapper").show();
            $("#reindex-log").html("");

            $(this).hide();

            console.log(algoliaAdminSettings)

            actions.push({ subaction: "handle_index_creation", name: "Handle index creation", sup: "" });

            for (value in algoliaAdminSettings.types)
            {
                var number = Math.ceil(algoliaAdminSettings.types[value].count / batch_count);

                for (var i = 0; i < number; i++)
                {
                    actions.push({
                        name: algoliaAdminSettings.types[value].name,
                        subaction: algoliaAdminSettings.types[value].type + "__" + i,
                        sup: (i + 1) + "/" + number
                    });
                }
            }

            actions.push({ subaction: "index_taxonomies", name: "Index taxonomies", sup: "" });

            actions.push({ subaction: "move_indexes", name: "Move all temp indexes", sup: "" });

            var i = 0;
            var call = function () {

                $.ajax({
                    method: "POST",
                    url: base_url,
                    data: { submitAlgoliaSettings: true, subaction: actions[i].subaction },
                    success: function (result) {
                        render(actions, i);
                    },
                    async: false
                });

                if (i < actions.length - 1)
                {
                    i = i + 1;
                    setTimeout(call, 1);
                }
                else
                {
                    $("#reindex-percentage").html(renderPercentage(100));
                    $(".close-results").show();
                }
            };

            render(actions, -1);
            setTimeout(call, 1);
        });
    });
});