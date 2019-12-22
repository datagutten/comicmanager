function use_release_date(object)
{
    const div = object.parentNode;
    const date = div.getElementsByClassName('date')[0].textContent;
    const input = div.getElementsByTagName('input')[0];
    input.value=date;
    generate_link(input)
}

function generate_link(object)
{
    const date = object.id;
    const link_span = document.getElementById('link_' + date);
    const comic = document.getElementById('comic').value;
    const site = document.getElementById('site').value;
    const root = document.getElementById('root').value;
    const mode = document.getElementById('mode').value;
    let url;

    if (mode==='original_date') {
        url = root + '/showcomics.php?comic=' + comic + '&view=date&site=%25&date=' + date;
    }
    else
        return;
    let link = document.createElement('a');

    link.setAttribute('href', url);
    console.log(url);
    link.textContent='Show ' + date;
    link_span.appendChild(link)
}