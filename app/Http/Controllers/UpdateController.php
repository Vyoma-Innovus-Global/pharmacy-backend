<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Registerstudent;
use Exception;
use App\Models\Token;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Models\SuperUser;


class UpdateController extends Controller
{
    public function details(Request $request)
    {
            
		$form_no	=	'PHARMA15778'; 
		$reg_data	=	Registerstudent::where('s_appl_form_num', $form_no)->get();
		#print_r($reg_data->toArray());
		#exit();
        return view('update/update_profile',[
			'user'	=>	$reg_data[0]
		]);        
    }
	
	public function update_details(Request $request)
    {
		$ref		=	 $request->form_num;   
	
		$reg_data	=	Registerstudent::where('s_appl_form_num', '=',  $ref )->get();
		$sess_year	=	$reg_data[0]->s_appl_reg_year;
		$form_number=	$reg_data[0]->s_appl_form_num;
		$time		= '';
		#print_r($reg_data);exit();
		if ($request->hasFile('student_image')) {
			$image = $request->file('student_image');
			$imageName = $form_number . '_image' . $time . '.' . $image->getClientOriginalExtension();
			$dir_path	=	'uploads/student_doc/' . $sess_year . '/';
			$photoPath =  $dir_path. $imageName;
			
			
			$image->storeAs($dir_path,$imageName, 'public');
		}		
		
		Registerstudent::where('s_appl_form_num', '=',  $ref )->update(
		[
			's_photo' => $photoPath,
			's_dob' => $request->dob
		]);
			
		return response()->json([
				'error' => false,
				'message' => 'Image Update successfully',
			], 200);
		 
			
    }

    
}
