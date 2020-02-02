let newcount = 1;
function add_category(event) //Create a new table row with an empty field
{
    console.log(event);
    if(event.type==="change" && event.target.value==='')
    {
        return;
    }

    const table=document.getElementsByTagName('table');
    const tr=document.createElement('tr');

    const td_name=document.createElement('td');
    const input_name=document.createElement('input');
    input_name.setAttribute('type','text'); //type="text"
    input_name.setAttribute('name',`new[${newcount}][name]`);
    input_name.addEventListener('change', add_category);
    td_name.appendChild(input_name); //Add the input to the td
    tr.appendChild(td_name); //Add the td to the tr

    const td_delete=document.createElement('td');
    tr.appendChild(td_delete); //Empty td, no need for delete button for new category

    const td_visible=document.createElement('td');
    const input_visible=document.createElement('input');
    input_visible.setAttribute('type','checkbox');
    input_visible.setAttribute('name',`new[${newcount}][visible]`);
    input_visible.setAttribute('value','1');
    input_visible.setAttribute('checked','checked');
    td_visible.appendChild(input_visible); //Add the input to the td
    tr.appendChild(td_visible); //Add the td to the tr

    table.item(0).appendChild(tr); //Add the tr to the table

    newcount++;
}

document.addEventListener("DOMContentLoaded", add_category);