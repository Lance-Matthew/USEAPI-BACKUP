<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Student\Student;

class StudentController extends Controller
{
    public function index(){
        $students = Student::with("profile", "studentBag", "notification")->get();
        return response()->json(['students' => $students]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentId'=>'required',
            'password'=>'required|min:8|max:100'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $student = Student::create([
            'studentId' => $request->studentId,
            'password' => Hash::make($request->password)
        ]);
    
        $student->profile()->create($request->input('profile'));
        $student->notification()->create(['stu_id' => $student->studentId]);
        $studentBag = $student->studentBag()->create(['stu_id' => $student->studentId]);
        $bookCollectionData = [
            'stubag_id' => $studentBag->id,
            'status' => 'INCOMPLETE'
        ];

        $studentBag->bookCollection()->create($bookCollectionData);
    
        return response()->json([
            'message' => 'Student Added',
            'data' => $student
        ], 200);
    }

    public function show($stu_id){
        $student = Student::with(["profile", "studentBag", "notification"])->where('studentId', $stu_id)->first();

        if(!$student){
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json(['student' => $student]);
    } 

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|max:100',
            'confirm_password' => 'required|same:password',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::find($id);
    
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
    
        $student->password = Hash::make($request->password);
        $student->save();
    
        return response()->json([
            'message' => 'Password updated successfully',
            'data' => $student
        ], 200);
    }
     public function destroy($id){
        $student = Student::find($id);
        $student -> delete();
        $student -> profile()->delete();
        $student -> studentBag()->delete();
        $student -> notification()->delete();
        return response()-> json(['message' => 'Student Removed']);
     }

     public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'studentId' => 'required|max:14',
            'password' => 'required|min:6|max:20',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Check if the student exists
        $student = Student::where('studentId', $request->studentId)->first();
    
        if (!$student) {
            return response()->json([
                'message' => 'Student not found'
            ], 404);
        }
    
        // Check if the password is correct
        if (Hash::check($request->password, $student->password)) {
            $token = $student->createToken('auth-token')->plainTextToken;
    
            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'data' => $student
            ], 200);
        } else {
            return response()->json([
                'message' => 'Incorrect credentials'
            ], 401); // Use 401 Unauthorized status code
        }
    }
    

    public function logout(Request $request){
        $request->student()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'user successfully logged out',
        ], 200);
    }
}