<!DOCTYPE html>
<html lang="en-us" xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#">
    <head>	
   <meta charset="utf-8">
    <title>Dero Pong Node</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

<style>
body{
	color:gold;
	background-color:#000;
}
.timer{float:right;}

#main div{
	border:1px solid grey;
	padding:5px;
	
}
#main > div{
	background-color:#252525;
	border:2px solid green;
	padding:10px;
	border-radius:3px;
}
#main > div > div > div > div{
	border:none;
	padding:5px;
}
#main > button{
	position:relative;
	top:10px;
}
.modal {
	background-color:#252525;
	box-shadow: 4px 1px 11px 5px #072904;
	box-sizing: border-box;
	max-width: 500px;
	max-height: 100%;	
	top: 0; 
	left: 0; 
	right: 0; 
	margin-left: auto; 
	margin-right: auto; 
	z-index:1;
	padding: 20px;	
	position: fixed;
	overflow: auto;	
}


	
.modal label,div.uuid{
	display:block;
	margin: 5px;
}
.modal label.hidden{
	display:none;
}
.modal .close,.modal .clear{
	float:right;
	padding:10px;
	border:1px solid grey;
	cursor: pointer;
	
}
.darken{
	top: 0; bottom: 0; left: 0; right: 0;
	position:fixed;
	background: rgba(0,0,0,.3);
}

.hidden{display:none;}


.modal, #main > div, #messages,#main > div, #transactions{
	 overflow-wrap: break-word;
	 line-height: 1.7em;
}
.modal input,.modal textarea,.modal select{
	background: black;
    color: #56ff04;
    padding: 5px;
}
select option{
	padding: 5px;
}	
.modal fieldset{
	margin:0 -5px;
}
.modal input[type='number']{
	width:100px;
}



#messages,#transactions{
	z-index:2;
	overflow-y: auto;
    max-height: 100%;
}

.warning{color:red;}


#products_header > div, #menu > div{display:inline-block;vertical-align:middle;}
#products_header,#menu{margin:10px;}
#menu button.selected{color:green;}



table.txns {
	word-break: break-word;
	width: 100%;
}
table.txns,table.txns th, table.txns td {
	border: 1px solid;
}
input[name="label"],
textarea[name="details"],
input[name="comment"],
input[name="out_message"],
input[name="api_url"],
input[name="scid"]{
	width:100%;
	  box-sizing: border-box;
}

#edit_product_modal input[name="comment"],
#edit_product_modal input[name="ask_amount"],
#edit_product_modal input[name="port"]{
	border: 1px solid #0f0;
}

.edit_i_address{
	border:1px solid grey;
	padding:3px;
}
.edit_i_address_address{
	 line-height: 1.3em;
}


span.info{
	color: #000;
	position:relative;
	cursor: pointer;
	width: 16px;
	height: 16px;
	display: inline-flex; 
	justify-content: center;
	align-items: center;
	background: #ddd;
	border-radius: 50%;
}
div.tip{
	position:absolute;
	bottom:20px;
	width:140px;
	background:#ddd;
	padding:3px;
	max-height:120px;
	overflow-y:auto;
	 line-height: 1.3em;
}

</style>
</head>
<body>
<div class="timer">
	<button id="pause">Pause</button> - 
	<div id="timer">0</div>
</div>

<div id="menu">
	<div>
		<button id="show_products" class="selected">Products</button>
	</div>
	<div>
		<button id="show_records">Records</button>
	</div>
	<div>
		<button id="show_settings">Settings</button>
	</div>

</div>

<hr>

<div id="messages" class="modal hidden"><div class="close">X</div><div id="message_list"></div></div>
<div id="transactions" class="modal hidden"><div class="clear">clear</div><div class="close">X</div><div id="transactions_list"></div></div>
<div id="products_header">
	<div>
		<button id="show_add_modal">Add Product</button>
	</div>
	<div>
		<button id="show_transactions">Show Transactions</button>
	</div>
	
</div>
<div class= "darken hidden"></div>


<div id="main">
	
</div>





<div id="add_product_modal" class="modal hidden">
	<div class="close">X</div>
	<h2>Add New Product / Service</h2>
	<form>
		<label>Product Type
			<select name="p_type" id="p_type">
				<option value="">Select Product Type</option>
				<option value="physical">Physical Goods</option>
				<option value="digital">Digital Goods</option>
				<option value="token">Token</option>
				<option value="smartcontract">S.C. Ownership Transfer</option>
			</select>
		</label>
		<label>Product Label
			<input id="label" name="label" type="text" >
		</label>
		<label>Product Details
			<textarea id="details" name="details" type="text" ></textarea>
		</label>
	
		
		
		<fieldset>
			<legend>Integrated Address Fields</legend>	
			<label>Comment <span class="info comment_info">i</span>
				<input id="comment" name="comment" type="text" >
			</label>
			<label>Ask Amount (atomic units)
				<input id="ask_amount" class="atomic_units dero" name="ask_amount" type="number" step="1"> <span class="token_units"></span>
			</label>
			<label>Port (uint 64)
				<input id="port" name="port" type="number" step="1">
			</label>
		</fieldset>

		<label>Out Message (max 128b) <span class="info out_message_info">i</span>
			<input id="out_message" name="out_message" type="text" maxlength="128">
		</label>		
		<div class="uuid">
		Use UUID <input id="add_out_message_uuid" name="out_message_uuid" type="checkbox" > <span class="info out_message_uuid_info">i</span>
		</div>
		<label>Api Url <span class="info api_url_info">i</span>
			<input id="api_url" name="api_url" type="text" maxlength="128">
		</label>
		<label>SCID <span class="info scid_info">i</span>
			<input id="scid" name="scid" type="text" >
		</label>		
		<label>Respond Amount (atomic units)
			<input id="respond_amount" class="atomic_units" name="respond_amount" type="number" step="1"> <span class="token_units"></span>
		</label>
		
		<label>Inventory
			<input id="inventory" name="inventory" type="number" step="1"> <span class="info inventory_info">i</span>
		</label>
		<br>
		<button role="button" id="add_product">Add Product</button>
	</form>
</div>

<div id="edit_product_modal" class="modal hidden">
	<div class="close">X</div>
	<h2>Edit Product / Service</h2>
	<form>
		<input id="pid" name="pid" type="hidden">
		<label>Product Type
			<select name="p_type" id="edit_p_type">
				<option value="">Select Product Type</option>
				<option value="physical">Physical Goods</option>
				<option value="digital">Digital Goods</option>
				<option value="token">Token</option>
				<option value="smartcontract">S.C. Ownership Transfer</option>
			</select>
		</label>
		
		<label>Product Label
			<input id="label" name="label" type="text" >
		</label>
		<label>Product Details
			<textarea id="details" name="details" type="text" ></textarea>
		</label>
		<label>Image
			<input type='file' />	
		</label>
			<img id="img" name="img" style="max-height:100px;" src="#">
					

		
		
		<fieldset>
			<legend>Integrated Address Fields (last used)</legend>		
			<label>Comment <span class="info comment_info">i</span>
				<input id="comment" name="comment" type="text" >
			</label>
			<label>Ask Amount (atomic units)
				<input id="ask_amount" class="atomic_units dero" name="ask_amount" type="number" step="1"> <span class="token_units"></span>
			</label>
			<label>Port (uint 64)
				<input id="port" name="port" type="number" step="1" >
			</label>
		</fieldset>
		<label>Out Message (max 128b) <span class="info out_message_info">i</span>
			<input id="edit_out_message" name="out_message" type="text" maxlength="128">
		</label>		
		<label>SCID <span class="info scid_info">i</span>
			<input id="edit_scid" name="scid" type="text" >
		</label>
		<div class="uuid">
		Use UUID <input id="edit_out_message_uuid" name="out_message_uuid" type="checkbox" > <span class="info out_message_uuid_info">i</span>
		</div> 
		<label>Api Url <span class="info api_url_info">i</span>
			<input id="api_url" name="api_url" type="text" maxlength="128">
		</label>	
		
		<label>Respond Amount (atomic units)
			<input id="edit_respond_amount"class="atomic_units" name="respond_amount" type="number" step="1"> <span class="token_units"></span>
		</label>
		<label>Inventory
			<input id="inventory" name="inventory" type="number" step="1"> <span class="info inventory_info">i</span>
		</label>
		<br>
		<div id="integrated_addresses">
		</div>
		<button role="button" id="edit_product">Update Product</button><button role="button" id="delete_product">Delete Product</button>
	</form>
</div>



<script>
//dir = document.location.pathname.split('/', 2);
var dir = '';
//manage the views
var viewing_state = 'products';
var menu = document.getElementById("menu");
var show_products_button = document.getElementById("show_products");
var show_records_button = document.getElementById("show_records");
var show_settings_button = document.getElementById("show_settings");
var products_header = document.getElementById("products_header");

show_products_button.addEventListener("click", (event) => {
	viewing_state = 'products';
	selectButton(show_products_button);
	main.innerHTML = '';	
	products_header.classList.remove("hidden");
	displayProducts(products_array);
})

show_records_button.addEventListener("click", (event) => {
	viewing_state = 'records';
	selectButton(show_records_button);
	main.innerHTML = '';
	products_header.classList.add("hidden");
	loadout();
})

show_settings_button.addEventListener("click", (event) => {
	viewing_state = 'settings';
	selectButton(show_settings_button);
	main.innerHTML = '';
	products_header.classList.add("hidden");
	settings('');
})

function selectButton(select_button){
	menu.querySelectorAll("button").forEach(function (button) {
		button.classList.remove("selected");
	});
	select_button.classList.add("selected");	
}


/*************************/
/* Show Settings */
/*************************/

function settings(form) {
	let formdata = JSON.stringify('{}');
	if(form!=''){
		formdata = new FormData(form);
		//formdata.append('register', 1);
	}
	
	async function getSettings(form) {
	  try {
		const response = await fetch(dir+"/settings.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin'
        },
		  body: formdata,
		});

		var result = await response.json();			

		createSettingsForm(result);
		api_url = document.querySelector('input[name="web_api_url"]').value;
		
		api_url
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	//const data = { mode: "year" };
	getSettings(form);
}

/*

edit_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;

	editProduct(form);
});	
*/
function createSettingsForm(ss) {
	main.innerHTML = '';
	let form = document.createElement('form');
	ss.forEach(function (setting, index, array) {
		
		let input = document.createElement('input');
		let text = document.createTextNode(setting.name +": ");		
		input.type = "text";
		input.name = setting.name;
		input.value = setting.value;
		let div = document.createElement('div');
		
		div.appendChild(text);
		div.appendChild(input);

		form.appendChild(div);

	});
	
	let edit = document.createTextNode("Save Settings");
	let button = document.createElement('button'); 
	button.appendChild(edit);
	button.addEventListener("click", (event) => {
		event.preventDefault();
		let form = event.target.parentElement;
		settings(form);		
	});	
	form.appendChild(button);
	
	let register = document.createTextNode("Register");
	let register_button = document.createElement('button'); 
	register_button.appendChild(register);
	register_button.addEventListener("click", (event) => {
		event.preventDefault();
		let form = event.target.parentElement;
		
		let input = document.createElement('input'); 
		input.type = "hidden";
		input.name = "register";
		input.value = 1;
		form.appendChild(input)
		settings(form);		
	});	
	form.appendChild(register_button);
	
	main.appendChild(form);
	
	
}

/*************************/
/* Show All Transactions */
/*************************/

function loadout() {
	async function getTransactions(data) {
	  try {
		const response = await fetch(dir+"/loadout.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		var result = await response.json();			

		main.innerHTML = createTable(result);
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	const data = { mode: "year" };
	getTransactions(data);
}


function createTable(table_data) {
  	var table = '<thead><th>Product Label</th><th>Comment</th><th>Amount</th><th>Out Message</th><th>Response Amt.</th><th>Buyer Address</th><th>Shipping Address</th><th>Out TXID</th><th>Time UTC</th></thead><tbody>';
	table_data.transactions.forEach(function (val, index, array) {
		let td='';
		td +='<td>'+ val.product_label + '</td>';
		td +='<td>'+ val.ia_comment + '</td>';
		td +='<td>'+ val.amount + ' (' + niceRound( val.amount * .00001) + ' Dero)' + '</td>';
		td +='<td>'+  val.res_out_message + '</td>';
		td +='<td>'+ val.out_amount + ' (' + niceRound( val.out_amount * .00001) + (val.type == 'token_sale'? ' Token': ' Dero') + ')</td>';		
		td +='<td>'+ val.buyer_address + '</td>';
		td +='<td style="white-space:pre">'+ val.ship_address + '</td>';
		td +='<td>'+ val.txid + '</td>';
		td +='<td>'+ val.time_utc + '</td>';
		
		
		table += '<tr>'+ td + '</tr>';
	});
	
	return '<table class="txns">'+table+'</tbody></table>';
}




/*****************************/
/* Products and I. Addresses */
/*****************************/

//images
 document.querySelector('input[type="file"]').addEventListener('change', function() {
	 
	 var files = event.target.files || event.dataTransfer.files;
	 var file = files[0];
    var reader = new FileReader();
			reader.onload = function(e) {
				//SaveName(file.name);
				 var img = document.querySelector('#img');
				 img.src = e.target.result;
				 resize();
			}
			reader.readAsDataURL(file);


  });


var products_array=[];

var main = document.getElementById("main");
var messages = document.getElementById("messages");
var transactions = document.getElementById("transactions");
var show_transactions_button = document.getElementById("show_transactions");

var add_product_modal = document.getElementById("add_product_modal");
var add_product_button = document.getElementById("add_product");
var show_add_modal_button = document.getElementById('show_add_modal');

var edit_product_modal = document.getElementById("edit_product_modal");
var edit_product_button = document.getElementById("edit_product");

var close_buttons = document.querySelectorAll('.close');
var darken_layer = document.querySelector('.darken');
var clear_buttons = document.querySelectorAll('.clear');

var out_messages = document.querySelectorAll('input[name="out_message"]');
var amount_inputs = document.querySelectorAll('.atomic_units');
/* show / hide modals */

show_add_modal_button.addEventListener("click", (event) => {
	add_product_modal.classList.remove("hidden");
	darken_layer.classList.remove("hidden");
});	

close_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.classList.add("hidden");
		
		if(event.target.parentElement.id!='messages' && event.target.parentElement.id!='transactions'){
			darken_layer.classList.add("hidden");
		}
	})
});	

clear_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.querySelector('#transactions_list').innerHTML = '';
	})
});	


show_transactions_button.addEventListener("click", (event) => {
	transactions.classList.remove("hidden");
})


var add_out_message_uuid = document.getElementById('add_out_message_uuid');
var edit_out_message_uuid = document.getElementById('edit_out_message_uuid');
/* form validation */
function getStringSize(event){ 
	let test = '';
	let addon = '';
	if(event.target.id == 'out_message' || event.target.id == 'add_out_message_uuid' || event.target.id == 'p_type'){
		test = document.getElementById('out_message');
		if(add_out_message_uuid.checked){
			addon = '550e8400-e29b-41d4-a716-446655440000';	
		}
	}else
	if(event.target.id == 'edit_out_message' || event.target.id == 'edit_out_message_uuid' || event.target.id == 'edit_p_type'){
		test = document.getElementById('edit_out_message');
		if(edit_out_message_uuid.checked){
			addon = '550e8400-e29b-41d4-a716-446655440000';		
		}		
	}
	 
	let size =  new Blob([test.value + addon]).size;

	if(size > 128){
		test.classList.add("warning");
	}else{
		test.classList.remove("warning");
	}
}
add_out_message_uuid.addEventListener('click', getStringSize, false);
edit_out_message_uuid.addEventListener('click', getStringSize, false);
out_messages.forEach((input) => {
input.addEventListener('input', getStringSize, false);
input.addEventListener('keyup', getStringSize, false);
input.addEventListener('blur', getStringSize, false);		
});	


function niceRound(number){
	return Math.round(number*100000000)/100000000;
}
function convert(input){	

	var unit_of_account = 'Token';
	
	
	
	if(input.classList.contains("dero")){
		unit_of_account = 'Dero';
	}
	
	
	
	if(input.parentElement.parentElement.querySelector("select[name='p_type']")){
		let selel = input.parentElement.parentElement.querySelector("select[name='p_type']");
		if(selel.value !='token'){
			unit_of_account = 'Dero';
		}
	}
	if(input.parentElement.parentElement.parentElement.querySelector("select[name='p_type']")){
		let selel = input.parentElement.parentElement.parentElement.querySelector("select[name='p_type']");
		if(selel.value !='token'){
			unit_of_account = 'Dero';
		}
	}
	
	
	
	var atunits = input.value;
	atunits = atunits * .00001;
	atunits =  niceRound(atunits);
	input.nextElementSibling.innerHTML = atunits+ " "+unit_of_account;
}
function callConvert(){
	convert(event.target);
}
amount_inputs.forEach((input) => {
input.addEventListener('input', callConvert, false);
input.addEventListener('keyup', callConvert, false);
input.addEventListener('blur', callConvert, false);		
});	


/******************/
/* Type selection */
/******************/
var p_type = document.getElementById("p_type");
var edit_p_type = document.getElementById("edit_p_type");

p_type.value='';
//reset on page reload
typeSelect('','');
//input[value="physical"] ~ input#scid{display:none;}
p_type.addEventListener("change", typeSelect );

edit_p_type.addEventListener("change", typeSelect,getStringSize );




function typeSelect(data,stored_value=false){
	let id,value;
	
	if(stored_value ===false){
		id = data.target.id;
		value = data.target.value;
	}else{
		id = 'edit_p_type';
		value = stored_value;
	}
	
	var modal;
	if(id=='p_type'){
		modal = add_product_modal;		
		convert(add_product_modal.querySelector("#respond_amount"));
	}else if(id=='edit_p_type'){
		modal = edit_product_modal;
		convert(edit_product_modal.querySelector("#edit_respond_amount"));
	}
	modal.querySelector('div.uuid').classList.remove("hidden");
	modal.querySelectorAll('label').forEach((label) => {
		label.classList.remove("hidden");
	});
	
	if(value == ""){
		modal.querySelector('input[name="out_message"]').placeholder='';	
	}	
	if(value == "physical"){	
		hideSCIDFields(id,modal);
		modal.querySelector('input[name="out_message"]').placeholder='Leave blank or UUID will be appended (Use UUID must be selected for shipping info)';
		
		if(api_url=='https://ponghub.com/papi'){
			modal.querySelector('input[name="out_message"]').placeholder='Register website with api url in settings';
		}
		modal.querySelector('input[name="out_message_uuid"]').checked =true;	
		
		modal.querySelector('input[name="api_url"]').placeholder='API URL to send UUID to if Use UUID is checked';
		if(id == 'p_type'){
			modal.querySelector('input[name="out_message"]').value ='';		
			//modal.querySelector('input[name="api_url"]').value = api_url;
		}
	}
	if(value == "digital"){
		hideSCIDFields(id,modal);
		modal.querySelector('input[name="out_message"]').placeholder='Link to E-Goods (https://news.com/eg1) or (https://news.com/eg?id=UUID)';
		modal.querySelector('input[name="api_url"]').placeholder='API URL to send UUID to if Use UUID is checked';
		if(id == 'p_type'){
			modal.querySelector('input[name="out_message"]').value ='';				
			modal.querySelector('input[name="api_url"]').value ='';
		}
	}
	if(value == "token"){		
		hideUUIDField(id,modal);
		hideAPIURLFields(id,modal);
		modal.querySelector('input[name="out_message"]').placeholder='Blank for SCID or add custom message';
		if(id == 'p_type'){
			modal.querySelector('input[name="out_message"]').value ='';
			modal.querySelector('input[name="out_message_uuid"]').checked =false;
			modal.querySelector('input[name="api_url"]').value ='';
		}
	}	
	if(value == "smartcontract"){		
		hideUUIDField(id,modal);
		hideAPIURLFields(id,modal);
		modal.querySelector('input[name="out_message"]').placeholder='Blank for SCID or add custom message';
		if(id == 'p_type'){
			modal.querySelector('input[name="out_message"]').value ='';
			modal.querySelector('input[name="out_message_uuid"]').checked =false;
			modal.querySelector('input[name="api_url"]').value ='';
		}
	}	
}



function hideSCIDFields(id,modal){
	modal.querySelector('input[name="scid"]').value = '';
	modal.querySelector('input[name="scid"]').parentElement.classList.add("hidden");
	if(id == 'edit_p_type'){
		modal.querySelectorAll('input.ia_scid,input.ia_respond_amount').forEach((inp) => {
			inp.value ='';
			inp.parentElement.classList.add("hidden");
		});		
	}
}
function hideAPIURLFields(id,modal){
	modal.querySelector('input[name="api_url"]').value = '';
	modal.querySelector('input[name="api_url"]').parentElement.classList.add("hidden");
}
function hideUUIDField(id,modal){
	modal.querySelector('input[name="out_message_uuid"]').checked =false;
	modal.querySelector('input[name="out_message_uuid"]').parentElement.classList.add("hidden");
}
/**********************/
/* Information / Help */
/**********************/
var showing_tip = false;
function showInfo(event){
	if(!showing_tip){		
		let div = document.createElement('div');
		
		let message = '';
		if(event.target.classList.contains('out_message_info')){
			showing_tip = true;
			
			message = 'For durable goods check "Use UUID" to ensure that the shipping info can be supplied. ';
			let text = document.createTextNode(message);
			div.appendChild(text);
			let hr = document.createElement('hr');
			div.appendChild(hr);
			
			message = 'For E-Products use this field for a link to reedeem the product. You can supply a url to append the UUID to if Use UUID is checked.';
			text = document.createTextNode(message);
			div.appendChild(text);
			hr = document.createElement('hr');
			div.appendChild(hr);
			
			message = 'For smart contracts leave blank to use the scid as the out message (or provide your own).';
			text = document.createTextNode(message);
			div.appendChild(text);
			
		}else if(event.target.classList.contains('out_message_uuid_info')){
			showing_tip = true;
			message = 'When selected it will generate a uuid and send it to the buyer and to the api link if provided. If a url or custom message is supplied in the out message, the UUID will be appended to the end if checked.';
			text = document.createTextNode(message);
			div.appendChild(text);
		}else if(event.target.classList.contains('api_url_info')){
			showing_tip = true;
			message = 'Specify an api end point url to send the uuid to when new transactions are confirmed';
			text = document.createTextNode(message);
			div.appendChild(text);			
		}else if(event.target.classList.contains('scid_info')){
			showing_tip = true;
			message = 'SCIDs here will be inherited by all blank integrated addresses below. This can be left blank or overridden by supplying scids at the I.A. level.';
			text = document.createTextNode(message);
			div.appendChild(text);
		}else if(event.target.classList.contains('comment_info')){
			showing_tip = true;
			message = 'This will appear in the wallet when the I.A. is entered. Editing the comment, ask amount or port will create a new I.A.';
			text = document.createTextNode(message);
			div.appendChild(text);			
		}else if(event.target.classList.contains('inventory_info')){
			showing_tip = true;
			message = 'Product level inventory is used first, if 0 then I.A. level inventory is used instead.';
			text = document.createTextNode(message);
			div.appendChild(text);			
		}


		
		div.classList.add('tip');
		event.target.appendChild(div);
		return;
	}
	showing_tip = false;	
	document.querySelectorAll(".tip").forEach(el => el.remove());
}

var infos = document.querySelectorAll('.info');
infos.forEach((info) => {
info.addEventListener('click', showInfo);
});	







/********************/
/* Display Products */
/********************/
function createSection(section){
	let div = document.createElement('div');
	let text = document.createTextNode(section);
	div.appendChild(text);
	return div;
}

/* load out the products */
function generateProduct(product) {
	
	
	let div = document.createElement('div');

	div.classList.add('product');
	div.setAttribute("data-productid",product.id);
	div.classList.add('product');
	
	let edit = document.createTextNode("Edit Product");
	let button = document.createElement('button'); 
	
	button.addEventListener("click", (event) => {

		editProducts(product.id);
	
		
	});	
	
	button.appendChild(edit);
	main.appendChild(button);
	div.innerHTML = ((product.image == null || product.image == '') ? '' : "<img style='max-height:50px;' src=" +product.image+">");
	div.appendChild(createSection("Type: " +product.p_type));
	div.appendChild(createSection("Label: " +product.label));
	div.appendChild(createSection("Details: " +product.details));	
	div.appendChild(createSection("Inventory: " +product.inventory));	
	div.appendChild(createSection("SCID: " +product.scid));	
	var unit_of_account = 'Dero';
	if(product.scid !=''){
		unit_of_account = 'Token';
	}
	div.appendChild(createSection("Respond Amount: " + product.respond_amount + " - (" + niceRound( product.respond_amount * .00001) + " "+unit_of_account+") - Applies to all I. Addrs. for this product"));	
	div.appendChild(createSection("Out Message: " + (product.out_message_uuid ==1 ? "UUID - "+ product.out_message:product.out_message) + " - Applies to all I. Addrs. for this product"));
	div.appendChild(createSection("Api Url: " +product.api_url));	

	var iaddresses = document.createElement('div');
	iaddresses.appendChild(createSection("Integrated Addresses"));
	
	product.iaddress.forEach(function (val, index, array) {
		var iaddress = document.createElement('div');

		iaddress.appendChild(createSection("Integrated Address: " +val.iaddr));
		iaddress.appendChild(createSection("Comment: " +val.comment));
		iaddress.appendChild(createSection("Ask Amount: " +val.ask_amount + " - (" + niceRound( val.ask_amount * .00001) + " Dero)"));
		iaddress.appendChild(createSection("Port: " +val.port));
		let status = "inactive";
		if(val.status == 1){
			status = "active";
		}
		iaddress.appendChild(createSection("Status: " + status));	
		if(product.scid !='' || val.ia_scid!=''){
			iaddress.appendChild(createSection("SCID: " + (val.ia_scid!=''?val.ia_scid:'inherited')));
			let show_amount = 'inherited';
			if(val.ia_respond_amount!=0){
				show_amount = " - (" + niceRound( val.ia_respond_amount * .00001) + " Token)"
			}		
			iaddress.appendChild(createSection("SCID Respond Amount: " + show_amount));		
		}
		
	
		
		iaddress.appendChild(createSection("Inventory: " + val.ia_inventory));	
		iaddresses.appendChild(iaddress);
		
	});
	
	div.appendChild(iaddresses);
	main.appendChild(div);
	
}


function displayProducts(products){
	  
	products.forEach(generateProduct);

}

/******************/
/* Initialization */
/******************/
var api_url = '';
function initialize(runit) {
	async function getProducts(data) {
	  try {
		const response = await fetch(dir+"/initialize.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		const result = await response.json();			
		
		let msgs = '';
		if(typeof result.errors != 'undefined'){
			for(var key in result.errors){
				msgs += result.errors[key] +' ';				
			}
			if(msgs !=''){
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		}
		//console.log("Success:", result);
		api_url=result.api_url;

		products_array=result.products;
		displayProducts(products_array);
		runit();
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	const data = { action: "initialize" };
	getProducts(data);
}


/****************/
/* Add Products */
/****************/
function addProduct(form) {
	async function submitProduct(form) {
	  try {
		const response = await fetch(dir+"/addproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: new FormData(form),
		});

		const result = await response.json();			
	
		if(result.success == false){
			let msgs = '';
			if(typeof result.errors != 'undefined'){
				for(var key in result.errors){
					msgs += result.errors[key] +' ';				
				}
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		
		}else{
			
			products_array.push(result.product);
			
			main.innerHTML = '';			
			displayProducts(products_array);
			add_product_modal.classList.add("hidden");
			darken_layer.classList.add("hidden");
		}
		
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	submitProduct(form);
}


add_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;

	addProduct(form);
});	





/*****************/
/* Edit Products */
/*****************/


function editProduct(form) {
	async function submitProduct(form) {
	
		var editform = new FormData(form);
		var fileInput = form.querySelector('#img');
		//console.log(fileInput.src);
		var src = '';
		if(fileInput.src != document.location && fileInput.src != document.location + '#'){	
			src = fileInput.src;
		}
		editform.append('image', src);
	  try {
		const response = await fetch(dir+"/editproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: editform,
		});

		const result = await response.json();			

		if(result.success == false){
			let msgs = '';
			if(typeof result.errors != 'undefined'){
				for(var key in result.errors){
					msgs += result.errors[key] +' ';				
				}
				//uncheck the boxes that can't be checked
				for(var key in result.failed_ia_ids){
					let failed_ia_id = result.failed_ia_ids[key];	
					form.querySelector('input[name="iaddress_status['+failed_ia_id+']"]').checked = false;;
				}
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		
		}else{
			main.innerHTML = '';
			products_array = result.products;
			displayProducts(products_array);
			editProducts(form.querySelector("#pid").value);
		}
	
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	submitProduct(form);
	
}

edit_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;

	editProduct(form);
});	




function editProducts(pid) {

	
	var editing = products_array.find(x => x.id == pid);

	edit_product_modal.querySelector("#pid").value = editing.id;
	
	edit_product_modal.querySelector("#edit_p_type").value = editing.p_type;
	
	
	edit_product_modal.querySelector("#label").value = editing.label;
	edit_product_modal.querySelector("#details").value = editing.details;	

	
	edit_product_modal.querySelector("#img").src = (editing.image == null ? '#' : editing.image);
	
	edit_product_modal.querySelector("#edit_out_message").classList.remove("warning");
	edit_product_modal.querySelector("#edit_out_message").value = editing.out_message;	
	edit_product_modal.querySelector("#edit_out_message_uuid").checked = (editing.out_message_uuid == 1? true:false);	
	edit_product_modal.querySelector("#api_url").value = editing.api_url;	
	
	edit_product_modal.querySelector("#edit_scid").value = editing.scid;	
	
	edit_product_modal.querySelector("#edit_respond_amount").value = editing.respond_amount;
	convert(edit_product_modal.querySelector("#edit_respond_amount"));
	edit_product_modal.querySelector("#inventory").value = editing.inventory;	
	
	edit_product_modal.querySelector("#integrated_addresses").innerHTML ='';
	let last_ia = editing.iaddress.length -1;
	
	editing.iaddress.forEach(function (iadd, index, array) {
		//Auto fill using last ia
		if(index == last_ia){
			edit_product_modal.querySelector("#comment").value = iadd.comment;
			edit_product_modal.querySelector("#ask_amount").value = iadd.ask_amount;
			convert(edit_product_modal.querySelector("#ask_amount"));
			edit_product_modal.querySelector("#port").value = iadd.port;
			
		}
		
		let ia_record = "<div class='edit_i_address'><label class='edit_i_address_address'>Integrated Address: "+iadd.iaddr+"</label>";
		ia_record += "<label>Comment: "+iadd.comment+"</label>";
		ia_record += "<label>Ask Amount: "+iadd.ask_amount+ " - (" + niceRound( iadd.ask_amount * .00001) + " Dero)"+"</label>";
		ia_record += "<label>Port: "+iadd.port+"</label>";
		ia_record += "Status Active?: ";
		let checkbox = '<input class="out_message_uuid" name="iaddress_status['+iadd.id+']" '+(iadd.status == 1?"checked":"")+' type="checkbox" >';
		
		ia_record += checkbox+"<br>";
		
		let ia_scid_input = '<input class="ia_scid" name="ia_scid['+iadd.id+']" value="'+iadd.ia_scid+'" type="text" >';
		ia_record += "<label>SCID: "+ia_scid_input+"</label>";
		
		let ia_respond_amount_input = '<input class="ia_respond_amount" name="ia_respond_amount['+iadd.id+']" value="'+iadd.ia_respond_amount+'" type="text" oninput="convert(this)">';
		ia_record += "<label>SCID Respond Amount: "+ia_respond_amount_input+'<span class="token_units">('+ niceRound(iadd.ia_respond_amount * .00001)+' '+(editing.p_type == 'token'?'Token':'Dero')+')</span></label>';
		
		let inv_input = '<input class="ia_inventory" name="ia_inventory['+iadd.id+']" value="'+iadd.ia_inventory+'" type="text" >';
		ia_record += "Inventory: "+inv_input+"<br>";
		ia_record +=  '<button onclick="deleteIA('+iadd.id+')" class="delete_button" >Delete I.A.</button>';
		ia_record +="</div>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += ia_record;
	});
	
	//Now run the same function that the select event triggers
	typeSelect('',editing.p_type);
	edit_product_modal.classList.remove("hidden");
	darken_layer.classList.remove("hidden");
}



/* start the program */	
window.addEventListener('load', function() {
	initialize(runit);	
});	











/*******************************/
/* Delete Products / IAdresses */
/*******************************/


function deleteItem(type,id) {
	async function submitDelete(data) {
	
	  try {
		const response = await fetch(dir+"/deleteitem.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		const result = await response.json();			

		if(result.success == false){
			let msgs = '';
			if(typeof result.errors != 'undefined'){
				for(var key in result.errors){
					msgs += result.errors[key] +' ';				
				}
				
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		
		}else if(result.success == true){
			main.innerHTML = '';
			products_array = result.products;
			

			if(data.type == 'iaddress'){
				editProducts(edit_product_modal.querySelector("#pid").value);
			}else{				
				edit_product_modal.classList.add("hidden");	
				darken_layer.classList.add("hidden");
			}
			displayProducts(products_array);
			
		}
	
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	const data = {action: "delete",  type: type, id: id};
	submitDelete(data);
	
}

var delete_product_button = document.getElementById("delete_product");

delete_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let result = confirm("Are you sure you want to delete this product?");
	if(result == false){
		return false;
	}	
	
	let form = event.target.parentElement;	
	let product_id = form.querySelector("#pid").value;
	deleteItem('product',product_id);
});	


function deleteIA(iaddress_id){	
event.preventDefault();
let result = confirm("Are you sure you want to delete this Integrated Address?");
if(result == false){
	return false;
}

deleteItem('iaddress',iaddress_id);
//console.log(result);

}



/*
function deIncProduct(pids){
	pids.forEach(function (product_id, index, array) {
		var product = products_array.find(x => x.id == product_id);
		product.inventory = parseInt(product.inventory) - 1;		
	});	

}

function deIncIAddress(pairs){
	pairs.forEach(function (pair, index, array) {
		var product = products_array.find(x => x.id == pair.product_id);
		
		var ia = product.iaddress.find(x => x.id == pair.i_address_id);
		ia.ia_inventory = parseInt(ia.ia_inventory) - 1;

		
	});	
	
}
*/


/* check for new transactions */
function checkWallet() {
	async function process() {
	  try {
		const response = await fetch(dir+"/process.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify({}),
		});

		const result = await response.json();			

		let msgs = '';
		if(typeof result.errors != 'undefined'){
			for(var key in result.errors){
				msgs += '<span style="color:red;">'+ result.errors[key] +'</span><hr>';				
			}
			
		}
		if(typeof result.messages != 'undefined'){
			for(var key in result.messages){
				msgs += '<span style="color:green;">'+ result.messages[key] +'</span><hr>';				
			}
			
		}
		if(typeof result.products != 'undefined'){
			
			products_array = result.products;
			if(viewing_state == 'products'){
				main.innerHTML = '';				
				displayProducts(products_array);
			}
			
		}
		
		if(msgs != ''){
			transactions.querySelector("#transactions_list").innerHTML += msgs;
			transactions.classList.remove("hidden");
		}
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}
	process();
	
}
	
	
/* check for new transactions */
var execute = function() {
	checkWallet();
}


/* timer */
var paused = false;
var pauseButton = document.getElementById('pause');
var alertTimerId =0;
pauseButton.addEventListener("click", (event) => {
	paused = !paused;
	if(paused){
		event.target.innerText = "Paused";
		clearTimeout(alertTimerId);
		clearInterval(running);
	}else{
	  
		event.target.innerText = "Pause";
		running = setInterval(runit, secs * 1000);
		startTimer();
	}
});

var runit = function() {
	if(!paused){
		execute();
		startTimer();
	}	
};	

function startTimer() {
	timer = secs;
	clearTimeout(alertTimerId);
	alertTimerId =  setTimeout(doTime, 1000);  
};	

var secs = 20;
var seconds = secs * 1000;
var running = setInterval(runit, secs * 1000);

var timer = secs;

var timerCountdown = document.getElementById('timer');
function doTime() { 

	var minutes, seconds;
    minutes = parseInt(timer / 60, 10)
    seconds = parseInt(timer % 60, 10);

	minutes = minutes < 10 ? "0" + minutes : minutes;
	seconds = seconds < 10 ? "0" + seconds : seconds;

	timerCountdown.innerText = minutes + ":" + seconds;
	if(!paused){
		if (--timer >= 0) {
			//Call self every second.
			alertTimerId =  	setTimeout(doTime, 1000); 
		}
	}
}





	
function resize(){

	var image= document.getElementById("img").src;		
		
	var canvas =document.createElement("canvas");
	var ctx = canvas.getContext("2d");
	var canvasCopy = document.createElement("canvas");
	var copyContext = canvasCopy.getContext("2d");				
			
		var img = new Image();
	img.crossOrigin = "Anonymous"; //cors support
	img.onload = function(){
		var W = img.width;
		var H = img.height;
		canvas.width = W;
		canvas.height = H;
		ctx.drawImage(img, 0, 0); //draw image
		
		var to_width = 500;
		var to_height = 500;
		var max_dimension = 500;
	//	if(W>max_dimension || H>max_dimension){
		
			if(W > H){
				let ratio = max_dimension / W; 
				to_height = H * ratio; 

			}else{		
				let ratio = max_dimension / H; 
				to_width = W * ratio; 
			}	

	//	}
		  
		  
		//resize
		canvas = resample_single(canvas, to_width, to_height, true);
		//alert($("#offTop").val());
		var imageData = ctx.getImageData(0,0, to_width,to_height);
		//crop
		var canvas1 = document.createElement("canvas");
		canvas1.width = to_width;
		canvas1.height = to_height;
		var ctx1 = canvas1.getContext("2d");
		ctx1.rect(0, 0, to_width,to_height);
		ctx1.fillStyle = 'white';
		ctx1.fill();
		ctx1.putImageData(imageData, 0, 0);	
		
		
		document.getElementById("img").src = canvas1.toDataURL();
		
	
		
	}
	img.src = image;
}	


/** this part from http://jsfiddle.net/9g9Nv/442/
 * Hermite resize - fast image resize/resample using Hermite filter. 1 cpu version!
 * 
 * @param {HtmlElement} canvas
 * @param {int} width
 * @param {int} height
 * @param {boolean} resize_canvas if true, canvas will be resized. Optional.
 */
function resample_single(canvas, width, height, resize_canvas) {
  var width_source = canvas.width;
  var height_source = canvas.height;
  width = Math.round(width);
  height = Math.round(height);

  var ratio_w = width_source / width;
  var ratio_h = height_source / height;
  var ratio_w_half = Math.ceil(ratio_w / 2);
  var ratio_h_half = Math.ceil(ratio_h / 2);

  var ctx = canvas.getContext("2d");
  var img = ctx.getImageData(0, 0, width_source, height_source);
  var img2 = ctx.createImageData(width, height);
  var data = img.data;
  var data2 = img2.data;

  for (var j = 0; j < height; j++) {
    for (var i = 0; i < width; i++) {
      var x2 = (i + j * width) * 4;
      var weight = 0;
      var weights = 0;
      var weights_alpha = 0;
      var gx_r = 0;
      var gx_g = 0;
      var gx_b = 0;
      var gx_a = 0;
      var center_y = (j + 0.5) * ratio_h;
      var yy_start = Math.floor(j * ratio_h);
      var yy_stop = Math.ceil((j + 1) * ratio_h);
      for (var yy = yy_start; yy < yy_stop; yy++) {
        var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half;
        var center_x = (i + 0.5) * ratio_w;
        var w0 = dy * dy; //pre-calc part of w
        var xx_start = Math.floor(i * ratio_w);
        var xx_stop = Math.ceil((i + 1) * ratio_w);
        for (var xx = xx_start; xx < xx_stop; xx++) {
          var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half;
          var w = Math.sqrt(w0 + dx * dx);
          if (w >= 1) {
            //pixel too far
            continue;
          }
          //hermite filter
          weight = 2 * w * w * w - 3 * w * w + 1;
          var pos_x = 4 * (xx + yy * width_source);
          //alpha
          gx_a += weight * data[pos_x + 3];
          weights_alpha += weight;
          //colors
          if (data[pos_x + 3] < 255)
            weight = weight * data[pos_x + 3] / 250;
          gx_r += weight * data[pos_x];
          gx_g += weight * data[pos_x + 1];
          gx_b += weight * data[pos_x + 2];
          weights += weight;
        }
      }
      data2[x2] = gx_r / weights;
      data2[x2 + 1] = gx_g / weights;
      data2[x2 + 2] = gx_b / weights;
      data2[x2 + 3] = gx_a / weights_alpha;
    }
  }
  //clear and resize canvas
  if (resize_canvas === true) {
    canvas.width = width;
    canvas.height = height;
  } else {
    ctx.clearRect(0, 0, width_source, height_source);
  }

  //draw
  ctx.putImageData(img2, 0, 0);
  
  return canvas;
}

	
	



</script>
</body>
</html>
