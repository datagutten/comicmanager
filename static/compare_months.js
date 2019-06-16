function add_key(comic, key, date, site) {
    console.log('Add ' + date + 'to ' + key);

    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            let response = JSON.parse(this.responseText);
            let release = response['release'];
            const element = document.getElementById(`add${release['site']}${release['date']}`);
            element.innerHTML = response['message'];
            element.removeAttribute('onclick');

            /* Add edit link */
            const link = document.createElement('a');
            link.setAttribute('href', 'edit_release.php?keyfield=uid&key=' + release['uid']);
            link.innerHTML = 'Edit uid ' + release['uid'];
            element.parentNode.appendChild(document.createElement('br'));
            element.parentNode.appendChild(link);
        }
    };
    xhttp.open("GET", `add_key.php?comic=${comic}&date=${date}&site=${site}&key=${key}`, true);
    xhttp.send();
}

function add_key_uid(comic, key, uid) {
    console.log('Add uid ' + uid + ' to ' + key);

    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            let response = JSON.parse(this.responseText);
            let release = response['release'];
            const element = document.getElementById(`add${release['site']}${release['date']}`);
            element.innerHTML = response['message'];
            element.removeAttribute('onclick');
        }
    };
    xhttp.open("GET", `add_key.php?comic=${comic}&key=${key}&uid=${uid}`, true);
    xhttp.send();
}

function add_field() 
{
    const div=document.getElementById('fields');

    const label_date=document.createElement('label');
    label_date.innerHTML="Start date:";
    div.appendChild(label_date);

    const input_date=document.createElement('input');
    input_date.setAttribute('type','text'); //type="text"
    input_date.setAttribute('name',"start[]");
    input_date.addEventListener('change', add_field);
    div.appendChild(input_date);

    const label_site=document.createElement('label');
    label_site.innerHTML="&nbsp;Site:";
    div.appendChild(label_site);

    const input_site=document.createElement('input');
    input_site.setAttribute('type','text'); //type="text"
    input_site.setAttribute('name',"site[]");

    div.appendChild(input_site);

    div.appendChild(document.createElement('br'));
}