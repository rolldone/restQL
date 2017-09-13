<?php

namespace App\Classes\weetQL;

use Illuminate\Http\Request;

trait weetQLController
{
    public $requireFilter = null;
    public $limit = [
        'take' => 50,
        'skip' => 0
    ];
	public function get(Request $request){

    }
    public function put(Request $request){

    }
    public function getRequireFilter($aa,$theFilter){
        $requireFilter = new RequireFilter($aa,$theFilter);
        return $requireFilter;
    }
    public function any(Request $request){
    	$ww = json_decode($request->restQl);
        $validator_valid = null;
        $isOverrideOpen = false;
        for($a=0;$a<count($ww);$a++){
            $gg = $ww[$a];
            $oo = $gg->model;
            $class = 'App\\'.$oo;
            $class = new $class();
            $originalClass = $class;
            if(isset($gg->backendFilter[0])){
            	for($aeo=0;$aeo<count($gg->backendFilter);$aeo++){
            		$class = $this->{$gg->backendFilter[$aeo]}($class);
            	}
            }
            if(isset($gg->with[0])){
            	for($ioi=0;$ioi<count($gg->with);$ioi++){
            		$class = $class->with($gg->with[$ioi]);
            	}
            }
            // jika menggunakan backend filter maka where tidak di perbolehkan
            // prioritas di backendFilter
            if(isset($gg->backendFilter[0]) == false){
                if(isset($gg->where[0])){
                	foreach ($gg->where as $key => $value) {
                         // echo ($value->field);
                         $class = $class->where($value->field,$value->operator,$value->value);
                    }
                }
            }else{
                if(isset($gg->where[0])){
                	return response()->json([
                        'status' => 'rejected',
                        'message' => 'You cant use backendFilter and where Clause together'
                    ],500);
                }
            }
            if(isset($gg->whereFilter[0])){
                for($aeo=0;$aeo<count($gg->whereFilter);$aeo++){
            		$class = $this->{$gg->whereFilter[$aeo]}($class,$gg,$request);
            	}
            }
            if(isset($gg->validator[0])){
                for($aio=0;$aio<count($gg->validator);$aio++){
                    $validator_valid = $this->{$gg->validator[$aio]}($class,$gg,$request);
                    if($validator_valid != null){
                        break;
                    }
                }
            }
            if($validator_valid != null){
                return response()->json([
                    'status' => 'error',
                    'message' => $validator_valid
                ],500);
            }
            // ini untuk override process default jadi full custom code di function
            // var_dump($gg->overrideProcess);
            if(isset($gg->overrideProcess)){
                return $this->{$gg->overrideProcess}($class,$gg,$request);
            }
            // get model class
            switch($gg->mode){
                case 'single':
                    if(isset($gg->column[0])){
                        $class = $class->first($gg->column);   
                    }else{
                        $class = $class->first(); 
                    }
                break;
                case 'all':
                    // limit row access
                    if(isset($originalClass->limit['max_row'])){
                        if($gg->take > 0){
                            if($originalClass->limit['max_row'] < $gg->take){
                                return response()->json([
                                    'status' => 'rejected',
                                    'message' => 'The row cant more than max row = '.$class->limit['max_row']
                                ],500);
                            }else{
                                if(isset($gg->column[0])){
                                    $class = $class->take($gg->take)->skip($gg->skip)->get($gg->column);
                                }else{
                                    $class = $class->take($gg->take)->skip($gg->skip)->get();
                                }
                            }
                        }else{
                            return response()->json([
                                'status' => 'rejected',
                                'message' => 'the row should be more than 0'
                            ],500);
                        }
                    }else{
                        if($gg->take >= 0){
                            return response()->json([
                                'status' => 'rejected',
                                'message' => 'Please define limiter at your model'
                            ],500);
                        }else{
                            if(isset($gg->column[0])){
                                $class = $class->take($this->limit['take'])->skip($this->limit['skip'])->get($gg->column);
                            }else{
                                $class = $class->take($this->limit['take'])->skip($this->limit['skip'])->get();
                            }
                        }
                    }
                    
                break;
            }
            if(isset($gg->field)){
                foreach ($gg->field as $key => $value) {
                     // echo ($value->field);
                     $class->{$value->field} = $value->value;
                }
            }
            if(isset($gg->backendField)){
                foreach ($gg->backendField as $key => $value) {
                    $class->{$value->field} = $this->{$value->func}($value->value);
                }
            }
            switch($gg->action){
            	case 'save':
                /*
                    if(isset($class->saveFirewall[0])){
                        $saveValid = false;
                        for($bio=0;$bio<count($class->saveFirewall);$bio++){
                            for($qqr=0;$qqr<count($gg->backendFilter);$qqr++){
                                if($class->saveFirewall[$bio] == $gg->backendFilter[$qqr]){
                                    $saveValid = true;
                                    break;
                                }
                            }
                        }
                        if($saveValid){
                            $class->save();
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'You are not have Privilge saving!'
                            ],500);
                        }
                    }else{
                        $class->save();
                    }
                    */
            	break;
            	case 'delete':
            		$class->delete();
            	break;
            	case 'saveReturn':
                /*
                    if(isset($class->saveFirewall[0])){
                        $saveValid = false;
                        for($bio=0;$bio<count($class->saveFirewall);$bio++){
                            for($qqr=0;$qqr<count($gg->backendFilter);$qqr++){
                                if($class->saveFirewall[$bio] == $gg->backendFilter[$qqr]){
                                    $saveValid = true;
                                    break;
                                }
                            }
                        }
                        if($saveValid){
                            $class->save();
                            return response()->json([
                                'status' => 'success',
                                'message' => $class
                            ],201);
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'You are not have Privilge saving!'
                            ],500);
                        }
                    }else{
                        $class->save();
                        return response()->json([
                            'status' => 'success',
                            'message' => $class
                        ],201);
                    }
                    */
            	break;
            	case 'get':
                    try{
                        return response()->json([
                            'status' => 'success',
                            'message' => $class
                        ],201);
                    }catch(\Exception $ex){
                        return response()->json([
                            'status' => 'error',
                            'message' => $ex->getMessage()
                        ],500);
                    }
            		
            	break;
            }

            // $class->{$gg->action}();

        }
    }
}

class RequireFilter{
    private $storeArrayFilter = [];
    private $isReadyBackendFilter = true;
    private $isEquals = false;
    private $saveRequireFilter = '';
    function __construct($gg,$arrayFunction){
        $this->storeArrayFilter = $arrayFunction;
        if(isset($gg->backendFilter[0])){
            for($aa=0;$aa<count($gg->backendFilter);$aa++){
                for($bb=0;$bb<count($arrayFunction);$bb++){
                    if($arrayFunction[$bb] == $gg->backendFilter[$aa]){
                        $this->isEquals = true;
                        break;
                    }else{
                        $this->saveRequireFilter = $arrayFunction[$bb];
                    }
                }     
                if($this->isEquals == false){
                    break;
                }
            } 
        }else{
            $this->isReadyBackendFilter = false;
        }
    }
    
    public function rejected(){
        if($this->isEquals == false) return true;
    }
    
    public function message(){
        if($this->isReadyBackendFilter){
            if($this->isEquals == false){
                return [
                    'status'=>'rejected',
                    'message'=>'need filter '.$this->saveRequireFilter
                ];
            } 
        }else{
            return [
                'status'=>'rejected',
                'message'=>'need filter '.implode(",",$this->storeArrayFilter)
            ];  
        }
    }
}

