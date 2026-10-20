
//GLOBALS VARS
$ = jQuery;
categories = [];
prices = [];
allLogs = [];


jQuery(document).ready(($) => {
	var data = {
		'action': 'get_values_categories'
	};
	jQuery.post(ajaxurl, data, (response) => {
		const object = JSON.parse(response);
		this.categories = object.categories;
		this.prices = object.prices;
	});
});

function clear_log() {
	var data = {
		'action': 'clear_log'
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, function(response) {
		window.location.reload();
	});
}

function save_ranges(){
	const table = document.getElementById("table_ranges");
	const inputs = table.getElementsByTagName("input");
	
	let RangesToSave = [];
	
	for(i = 0; i<inputs.length; i+=3){
		const desde = inputs[i].value;
		const hasta = inputs[i+1].value;
		const value = inputs[i+2].value;
		
		RangesToSave.push([desde, hasta, value]);
	}
	
	const select_categorie = document.getElementById("select_categorie");
	//select_categorie es el ID del select
	this.prices[select_categorie.selectedIndex] = {
		//se obtiene el nombre 
		name: select_categorie.options[select_categorie.selectedIndex].text,
		id: select_categorie.value,
		values: RangesToSave
	}
	//* se desactiva el panel de guardar
	fn_loading_categories();
	const ck = document.getElementById("create_category_input");
	const download_categories_saved_prices = document.getElementById("download_categories_saved_prices");

	// const bodega = obtenerValorDeBodega();
	var data = {
		'action': 'saveRanges',
		'dataRanges': this.prices,
		'create_category' : ck.checked,
		'download_categories_saved_prices' : download_categories_saved_prices.checked,
		// 'bodega_seleccionada': bodega
	};
	console.log(data);
	jQuery.post(ajaxurl, data, function(response) {
		fn_deactivate_loading_categories();
	});
}

function agregaRange(){
	const table_ranges = document.getElementById("table_ranges");
	//lo incerta en la penultima fila (es el boton de agregar rango)
	var row = table_ranges.insertRow(table_ranges.rows.length-1);
	
	
	//TXT
	const txt = document.createElement('input');
    txt.type = 'number';

	const x = table_ranges.rows.length;
	let cell = row.insertCell();
	cell.innerHTML = x-1;
	
	const txt1 = document.createElement('input');
    txt1.type = 'number';
	const cell1 = row.insertCell();
	cell1.appendChild(txt1);
	
	const txt2 = document.createElement('input');
    txt2.type = 'number';
	const cell2 = row.insertCell();
	cell2.appendChild(txt2);
	
	let cell3 = row.insertCell();
	cell3.innerHTML = "&nbsp; &nbsp;";
	
	const txt3 = document.createElement('input');
    txt3.type = 'number';
	const cell4 = row.insertCell();
	cell4.appendChild(txt3);

}

// FUNCIONES DE ANIMACION DE CARGA
//=======================================================

function fn_loading_console() {
	const loader_console = document.getElementById("loader_console");
	loader_console.style.display = "flex";
	
	const btn_PriInv = document.getElementById("btn_PriInv");
	btn_PriInv.disabled = true;

	const btn_Pri = document.getElementById("btn_Pri");
	btn_Pri.disabled = true;
	
	const btn_Cata = document.getElementById("btn_Cata");
	btn_Cata.disabled = true;
	const btn_Cataext = document.getElementById("btn_Cataext");
	btn_Cataext.disabled = true;

	const btn_delete_products = document.getElementById("btn_delete_products");
	btn_delete_products.disabled = true;
	

	const btn_update_brands = document.getElementById("update_brands");
	btn_update_brands.disabled = true;

	const btn_deleteAllCategories = document.getElementById("deleteAllCategories");
	btn_deleteAllCategories.disabled = true;
}

function fn_loading_categories() {
	const panel_loader_categories = document.getElementById('loader_categories');
	panel_loader_categories.style.display = 'flex';
}

function fn_deactivate_loading_categories() {
	const panel_loader_categories = document.getElementById('loader_categories');
	panel_loader_categories.style.display = 'none';
}
//=======================================================

function delete_row(number){
	const table_ranges = document.getElementById("table_ranges");
	table_ranges.rows[number+1].remove();
}

function update_hourly_event(event){
	var data = {
		'action': 'do_hourly_event',
		event: event
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, function(response) {
		
	});
	
	setTimeout(()=>{
		window.location.reload();
	}, 3000);	
}

function update_daily_event(){
	
	var data = {
		'action': 'do_daily_event'
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, function(response) {
		
	});
	
	
	setTimeout(()=>{
		window.location.reload();
	}, 5000);
}

function update_monthly_event(){
	
	var data = {
		'action': 'do_monthly_event'
	};

	jQuery.post(ajaxurl, data, function(response) {
		
	});
	fn_loading_console();
	setTimeout(()=>{
		window.location.reload();
	}, 5000);
	
}

function verificar_license_intcomex(){
	
	var data = {
		'action': 'do_license_verify'
	};
	
	fn_loading_console();

	jQuery.post(ajaxurl, data, function(response) {
		window.location.reload();
	});
}

function clear_queue(){
	var data = {
		'action': 'clear_queue_intcomex'
	};
	
	fn_loading_console();

	jQuery.post(ajaxurl, data, function(response) {
		window.location.reload();
	});
}

function get_categories_intcomex() {
	const panel_download_categories = document.getElementById('load_categorias');
	panel_download_categories.style.display = 'none';

	const panel_loader_categories = document.getElementById('loader_categories');
	panel_loader_categories.style.display = 'flex';

	var data = {
		'action': 'get_categories_intcomex'
	};

	jQuery.post(ajaxurl, data, (response) => {
		this.categories = JSON.parse(response);
		panel_loader_categories.style.display = 'none';
	});
}

async function create_category() {

	let object_values = {};
	
	const categories_valides = this.categories.filter((e)=> ! this.prices.find(price=> e.id===price.id) );

	categories_valides.sort(dynamicSort("name"));
	
	categories_valides.forEach(element => {
		object_values[element.id] = "[" + element.id + "] " + element.name;
	});
	
	const { value:categorie } = await Swal.fire({
		title: 'Selecciona una categoria',
		input: 'select',
		inputOptions: object_values,
		inputPlaceholder: 'Selecciona una categoria',
		showCancelButton: true,
		inputValidator: (value) => {
		  return new Promise((resolve) => {
			if (value) {
			  resolve()
			} else {
			  resolve('Necesitas seleccionar una categoria D:')
			}
		  })
		}
	})
	  
	if (categorie) {
		const name = this.categories.find(e=> e.id == categorie).name; 
		$("#select_categorie").append($('<option>', {
			value: categorie,
			text: name
		}));
		this.prices.push({
			name: name,
			id: categorie,
			values: [
				[0,99999, 10]
			]
		});
		const select_categorie = document.getElementById("select_categorie");
		select_categorie.options[select_categorie.options.length-1].selected = 'selected';
		select_categorie_price(select_categorie);
	}
}

function dynamicSort(property) {
    var sortOrder = 1;
    if(property[0] === "-") {
        sortOrder = -1;
        property = property.substr(1);
    }
    return function (a,b) {
        /* next line works with strings and numbers, 
         * and you may want to customize it to your needs
         */
        var result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
        return result * sortOrder;
    }
}


function select_categorie_price(categorie) {
	
	limpiarFilas();

	const table_ranges = document.getElementById("table_ranges");
	const priceSelected = this.prices[categorie.selectedIndex]?.values || [];

	//
	//* Es establece los precios
	for (const iterator of priceSelected) {
		var row = table_ranges.insertRow();
		//no de fila
		const x = table_ranges.rows.length;
		let cell = row.insertCell();
		cell.innerHTML = x-1;
		
		//txt "De"
		const txt1 = document.createElement('input');
		txt1.type = 'number';
		txt1.value = iterator[0];
		const cell1 = row.insertCell();
		cell1.appendChild(txt1);
		
		//txt "Hasta"
		const txt2 = document.createElement('input');
		txt2.type = 'number';
		txt2.value = iterator[1];
		const cell2 = row.insertCell();
		cell2.appendChild(txt2);

		//separador
		let cell3 = row.insertCell();
		cell3.innerHTML = "&nbsp; &nbsp;";
		
		//txt "Cantidad porcentaje"
		const txt4 = document.createElement('input');
		txt4.type = 'number';
		txt4.value = iterator[2];
		const cell4 = row.insertCell();
		cell4.appendChild(txt4);	
	}

	// se agrega el boton de agregar rango
	var row = table_ranges.insertRow();

	
	const cell4 = row.insertCell();
	cell4.setAttribute('colspan', '5');

	//Div que centra el botn
	const divCenter = document.createElement('div');
	divCenter.classList.add("flex-center");
	cell4.appendChild(divCenter);	

	//Boton
	const button = document.createElement('button');
	button.textContent = "Agregar rango"
	button.classList.add("button");
	button.classList.add("button-primary");
	button.addEventListener("click", agregaRange)
	divCenter.appendChild(button);	
	//TXT
	
}

//Elimina todas la filas excepto la primera y la ultima
function limpiarFilas() {
	const tableHeaderRowCount = 1;
	const table = document.getElementById('table_ranges');
	const rowCount = table.rows.length;
	for (var i = tableHeaderRowCount; i < rowCount; i++) {
		table.deleteRow(tableHeaderRowCount);
	}
}

//borra una categoria
function delete_category() {
	const select_categorie = document.getElementById("select_categorie");
	//select_categorie es el ID del select
	const indexSelected = select_categorie.selectedIndex;
	if (indexSelected == 0) {
		Swal.fire(
			'Error!',
			'No se puede borrar la categoria general',
			'error'
		  )
		  return;
	}
	select_categorie.remove(indexSelected);
	this.prices.splice(indexSelected, 1);
	select_categorie.options[indexSelected-1].selected = 'selected';
	select_categorie_price(select_categorie);
	
}

//filtra el log por el select
function filterLog(select) {
	const select_filer = document.getElementById('select_filter');
	const messages = document.getElementById('messages');

	if(this.allLogs.length == 0){
		this.allLogs = [...messages.getElementsByTagName('li') ];
	}

	messages.innerHTML = '';
	const result = this.allLogs.map((element)=>{
		element.textContent.includes(select_filer.value) ?
		messages.append(element) : null
	});

	if(messages.lastElementChild){
		messages.lastElementChild.scrollIntoView({block: "end",  behavior: "smooth"});
	}
	
}

//descarga el log
function downloadLog() {
	const select_log = document.getElementById('select_log');
	
	var data = {
		'action': 'get_path_intcomex_plugin'
	};
	
	jQuery.post(ajaxurl, data, (response) => {
		console.log(response);
		const res = JSON.parse(response);
		const name_file = select_log.value + ".json";
		const path_file = res.path + "/inc/" + name_file;
		console.log(path_file);
		download(path_file, name_file);
	});
	
}

//funcion de descargar archivo
function download(url, filename) {
	fetch(url)
	  .then(response => response.blob())
	  .then(blob => {
		const link = document.createElement("a");
		link.href = URL.createObjectURL(blob);
		link.download = filename;
		link.click();
	})
	.catch(console.error);
  }

  function deleteAllCategories() {
	var data = {
		'action': 'deleteAllCategories'
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, (response) => {
		window.location.reload();
	});
}

function obtenerValorDeBodega() {
	var fieldset = document.getElementById('idBodega');
	
	// Obtener todos los elementos input dentro del fieldset
	var inputs = fieldset.getElementsByTagName('input');
	
	// Recorrer los elementos input para encontrar el seleccionado
	for (var i = 0; i < inputs.length; i++) {
		var input = inputs[i];
		
		// Verificar si el input es de tipo radio y está seleccionado
		if (input.type === 'radio' && input.checked) {
						
			// Hacer algo con el valor seleccionado
			return input.value;
			
			// Romper el bucle ya que encontramos el seleccionado
			break;
		}
	}
}

function update_brands() {
	var data = {
		'action': 'update_brands'
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, (response) => {
		window.location.reload();
	});
}

function delete_products_out_stock_inc() {
	var data = {
		'action': 'delete_products_out_stock_inc',
		'sn_borrar' : 0
	};
	fn_loading_console();
	jQuery.post(ajaxurl, data, (response) => {
		response = JSON.parse(response);

		if (response.count > 0) {
			Swal.fire({
				title: response.message + ", esta seguro?",
				text: "No se puede revertir este proceso",
				icon: "warning",
				showCancelButton: true,
				confirmButtonColor: "#3085d6",
				cancelButtonColor: "#d33",
				confirmButtonText: "Sí",
				cancelButtonText: "No"
			  }).then((result) => {
				if (result.isConfirmed) {
					confirmed_delete_products();
				}else
				{
					window.location.reload();
				}
			});	
		}else{
			Swal.fire({
				title: "Nada que borrar",
				text: "No hay nigun producto de intcomex que tenga stock 0",
				icon: "success"
			}).then((result) => {
				window.location.reload();
			}).catch((err) => {
				console.log(err);
			});
			
		}
	});	
}

function confirmed_delete_products() {
	
	var data = {
		'action': 'delete_products_out_stock_inc',
		'sn_borrar' : 1
	};

	jQuery.post(ajaxurl, data, (response) => {
		response = JSON.parse(response);
		Swal.fire({
			title: "Borrado!",
			text: "Se han borrado " + response.count + " productos",
			icon: "success"
		}).then((result) => {
			window.location.reload();
		}).catch((err) => {
			console.log(err);
		});
	});
	
}