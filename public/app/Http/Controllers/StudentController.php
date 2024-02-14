<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Resources\Guardian as GuardianResource;
use App\Http\Resources\GuardianCollection;
use App\Http\Resources\Student as StudentResource;
use App\Http\Resources\StudentCollection;
use App\Http\Resources\Transaction as TransactionResource;
use App\Model\Guardian;
use App\Model\Student;
use Illuminate\Http\Request;
use DB;

class StudentController extends Controller
{
    public function getRegisteredDBKids(Request $request)
    {
        $students = Student::with('user')->latest();

        if ($request->search) {
            $students = $students->where('name', 'like', "%{$request->search}%");
        }

        // $students = $students->whereHas('user', function ($query){
        //     $query->where('role_id', RoleType::Student);
        // });

        if ($request->max_rows) {
            $students = $students->paginate($request->max_rows);
        } else {
            $students = $students->get();
        }

        return new StudentCollection($students);
    }

    public function getStudentDetails($id)
    {
        $user = auth()->user();
        $student = Student::with('guardians.user', 'user', 'school', 'registrations')->find($id);

        if (! $student) {
            return response()->json(['errors' => ['exist' => ['Student Not Found']], 'message' => '', 'status' => false], 422);
        }
        $student->is_detail = true;

        return new StudentResource($student);
    }

    public function getGuardianDetails($id)
    {
        $user = auth()->user();
        if ($user) {
            $guardian = Guardian::with('user', 'students.user', 'students.school' , 'registrations')->where('id', $id)->first();
        }

        return new GuardianResource($guardian);
    }
    public function getStudentTransactionDetail($id)
    {
        $user = auth()->user();
        $order =  DB::connection('partner_mysql')->table('orders')
        ->where('code',$id)->first();
        if (! $order) {
            return response()->json(['errors' => ['exist' => ['transaction Not Found']], 'message' => '', 'status' => false], 422);
        }
        
        return new TransactionResource($order);
    }

    public function getRegisteredGuardians(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $guardians = Guardian::with('user.avatar', 'students.user')->latest();

            $guardians = $guardians->whereHas('user', function ($query) use ($user , $request) {
                $query->where('role_id', RoleType::Guardian);
                $query = $query->where('name', 'like', "%{$request->search}%");
            });

            if ($request->maxRows) {
                $guardians = $guardians->paginate($request->maxRows);
            } else {
                $guardians = $guardians->get();
            }

            return new GuardianCollection($guardians);
        }

        return 'user not found';
    }

    public function activateDeactivateGuardians(Request $request)
    {
        $students = Guardian::with('user')->whereIn('id', $request->studentUserIds)->get();

        foreach ($students as $student) {
            $user = $student->user;
            $user->update(['is_active' => $request->activate]);
        }

        return response(['success' => ['Status Updated Successfully'], 'status' => false, 'message' => ''], 200);
    }

    public function activateDeactivateStudents(Request $request)
    {
        $students = Student::with('user')->whereIn('id', $request->studentUserIds)->get();

        foreach ($students as $student) {
            $user = $student->user;
            $user->update(['is_active' => $request->activate]);
        }

        return response(['success' => ['Status Updated Successfully'], 'status' => false, 'message' => ''], 200);
    }
}
