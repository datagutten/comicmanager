function set_category(event) {
    const category = event.target;
    const inputs = document.getElementsByClassName('input_category');
    for(let i=0; i<inputs.length; i++)
    {
        console.log(inputs.item(i));
        inputs.item(i).selectedIndex = category.selectedIndex;
    }
}

function prepare_categories() {
    const select = document.getElementById('category');
    if(select===null) //Comic without categories
        return;
    select.addEventListener("change", set_category);
    const inputs = document.getElementsByClassName('input_category');

    for(let i=inputs.length-1; i>=0; i--)
    {
        let sub_select = select.cloneNode(true);
        sub_select.name = inputs[i].name;
        sub_select.id = inputs[i].id;
        sub_select.className = inputs[i].className;
        sub_select.value = inputs[i].value;

        inputs[i].replaceWith(sub_select);
    }
}

document.addEventListener("DOMContentLoaded", prepare_categories);