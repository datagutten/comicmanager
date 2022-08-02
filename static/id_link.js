$(document).ready(function () {
    $('img.tooltip').hide()
    $('a.link').hide()

    $("input.release_id").on('keyup', function (event) {
        const value = event.target.value
        const date = event.target.id
        const link_id = 'link_' + event.target.id
        const link = $('a#' + link_id)
        const tooltip = $(`img#tooltip_${date}`)

        const comic = document.getElementById('comic').value;
        const site = document.getElementById('site').value;
        const root = document.getElementById('root').value;
        const mode = document.getElementById('mode').value;
        if (value.length === 0) {
            link.hide()
            return;
        }

        link.show()

        if (mode === 'id') {
            link.val(`Show ${comic} ${mode} ${value}`)

            $.get(`${root}/management/image_ajax.php?comic=${comic}&key_field=${mode}&key=${value}`, function (url) {
                if (url.length > 0) {
                    tooltip.attr('src', url)
                    link.attr('href', url)
                } else {
                    link.hide()
                }
            })

            link.on('mousemove', function (e) {
                tooltip.stop(1, 1).show()
                tooltip.offset({
                    top: e.pageY + 20,
                    left: e.pageX + 10
                });
            })

            link.mouseleave(function () {
                tooltip.hide();
            });

        } else {
            link.attr('href', root + '/showcomics.php?comic=' + comic + '&view=date&site=%25&date=' + value)
            link.val(`Show ${comic} ${site} ${value}`)
        }
    })
})
