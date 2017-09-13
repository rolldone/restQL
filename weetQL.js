define(function(require){
	let {
		restQL,
		getRestApiService
	} = require('./httpServices.js');
	let gg = function(){
		let theJSON = function(){
			// ALL
			this.model = '';
			this.field = [];
			this.fieldCheck = {};
			this.backendField = [];
			this.backendFilter = [];
			this.validator = [];
			this.overrideProcess = null;
			this.where = [];
			this.whereFilter = [];
			this.column = [];
			this.take = 10;
			this.skip = 0;
			this.mode = 'single';
			// laravel
			this.with = [];
			this.init = function(){
				// ALL
				this.model = '';
				this.field = [];
				this.fieldCheck = {};
				this.where = [];
				this.backendField = [];
				this.column = [];
				this.backendFilter = [];
				this.overrideProcess = null;
				this.validator = [];
				this.mode = 'single';
				this.take = 10;
				this.skip = 0;
				// laravel
				this.with = [];
			}
			this.init();
		}
		this.newJSON = new theJSON();
		this.queryStore = [];
		this.init = function(model,mode){
			this.newJSON = new theJSON();
			vm.newJSON.model = model;
			vm.newJSON.mode = mode;
		}
		// untuk define field dari server
		this.setBackendField = function(nameField,value,func){
			vm.newJSON.backendField.push({
				field : nameField,
				value : value,
				func : func
			});
			vm.newJSON.fieldCheck[nameField] = value;
		}
		this.setField = function(nameField,value){
			vm.newJSON.field.push({
				field : nameField,
				value : value
			})
			vm.newJSON.fieldCheck[nameField] = value;
		}
		// becareful use this
		// your all function gonna insert all
		this.setFieldObject = function(theObject){
			vm.newJSON.field = [];
			for(var key in theObject){
				// console.log(key,theObject[key]);
				vm.newJSON.field.push({
					field : key,
					value : theObject[key]
				});
				
				vm.newJSON.fieldCheck[key] = theObject[key];
			}
			// vm.newJSON.field.push(theObject);
		}
		this.setWhereFilter = function(theRuleName){
			vm.newJSON.whereFilter.push(theRuleName);
		}
		this.where = function(field,operator,value){
			vm.newJSON.where.push({
				field : field,
				operator : operator,
				value : value
			})
		}
		this.setColumn = function(column){
			vm.newJSON.column.push(column);
		}
		this.setValidator = function(func){
			vm.newJSON.validator.push(func);
		}
		this.setOverrideProcess = function(func){
			vm.newJSON.overrideProcess = func;
		}
		this.setBackendFilter = function(func){
			vm.newJSON.backendFilter.push(func);
		}
		this.setWith = function(withValue){
			vm.newJSON.with.push(withValue);
		}
		this.setTakeAndSkip = function(take,skip){
			vm.newJSON.take = take;
			vm.newJSON.skip = skip;
		}
		this.get = function(){
			vm.newJSON.action = 'get'; 
			vm.queryStore.push(Object.assign({},vm.newJSON));
		}
		this.saveReturn = function(){
			vm.newJSON.action = 'saveReturn'; 
			let gg = Object.assign({},vm.newJSON);
			vm.queryStore.push(Object.assign({},gg));
		}
		this.save = function(){
			vm.newJSON.action = 'save'; 
			let gg = Object.assign({},vm.newJSON);
			vm.queryStore.push(Object.assign({},gg));
		}
		this.delete = function(){
			vm.newJSON.action = 'delete';
			vm.queryStore.push(Object.assign({},vm.newJSON));
		}	
		this.export = function(){
			return JSON.stringify(vm.queryStore);
		}
		this.executing = function(param,callback){
			console.log(param);
			var pp = {
				restQl : vm.export()
			}
			pp = Object.assign(pp,param);
			getRestApiService(JSON.stringify(pp),restQL.any,function(data){
				callback(data);
			})
		}
		let vm = this;
		return vm;
	}
	return gg;
})
