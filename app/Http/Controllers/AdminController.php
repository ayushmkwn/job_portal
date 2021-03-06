<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail1;
use App\Models\applications;
use App\Models\Company;
use App\Models\Student;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    //

    public function index()
    {
        $users = User::all();

        return view('admin.index');
    }

    public function addCompany()
    {
        return view('admin.addCompany');
    }


    public function confirmCompany(Request $request)
    {
        $cname = $request->get('cname');
        $crole = $request->get('jr');
        $ctc = $request->get('ctc');
        $gpa = $request->get('mcgpa');

        $file = $request->file('file');

        $arr = $request->get('eb');
        $arr = implode(',', $arr);

        $date=$request->get('ldate');
        $filename = time() . '.' . $file->getClientOriginalExtension();

        $sql = Company::query()->select()->where('company_name', '=', $cname)->get();


        $request->file->move('assets', $filename);
        if ($sql->isEmpty()) {
            $company = new Company();
            $company->company_name = $cname;
            $company->job_role = $crole;
            $company->eligibility = $arr;
            $company->CTC = $ctc;
            $company->minimum_CGPA = $gpa;
            $company->job_description = $filename;
            $company->last_date=$date;
            $company->save();
            return back()->with('success', 'Company Data Inserted Successfully');
        } else {
            return back()->with('info', 'Company Data Already Exists');
        }

    }

    public function review()
    {
        $sql = Student::query()->select()->where('status', '=', 'Pending')->get();

        //dd($sql);
        if ($sql->isEmpty()) {
            return view('admin.reviews', compact('sql'));
        } else {
            return view('admin.reviews', compact('sql'));
        }

    }

    public function confirm(Request $request)
    {


        $sql = Student::query()->select()->where('id', '=', $request->submit)->get();


        $sql[0]->status = 'Done';
        $sql[0]->save();

        Mail::send(new ContactMail1($request));
        return back()->with('success', 'Verification Done');
    }

    public function application()
    {
        $company = Company::query()->select()->get();
        $students = applications::query()->select()->get();
        $user = auth()->user();
        $profile = Student::query()->select()->get();

        //dd($profile);
        $roles = [];
        $companies = [];
        $uroles = [];
        $ucompanies = [];
        for ($i = 0; $i < count($company); $i++) {
            array_push($roles, $company[$i]->job_role);
            array_push($companies, $company[$i]->company_name);

        }
        $ucompanies = array_unique($companies);
        $uroles = array_unique($roles);
        //dd($ucompanies,$uroles);
        return view('admin.applicants', compact('ucompanies', 'uroles', 'students', 'profile'));
    }

    public function applicants(Request $request)
    {


        $cn = $request->get('cn');
        $jr = $request->input('jr');

        $students = applications::query()->select()->where('company_name', '=', $cn)->
        where('job_role', '=', $jr)->get();
        dd($students);
        return view('admin.showapplicants', compact('students'));

    }

    public function dd(Request $request)
    {
        $sec = $request->get('select');
        $rej = $request->get('reject');

        if ($sec) {
            $app = applications::query()->select()->where('id', '=', $sec)->get();

            $cn = $app[0]->company_name;
            $jr = $app[0]->job_role;
            $roll = $app[0]->rollno;
            $company = Company::query()->select()->where('company_name', '=', $cn)
                ->where('job_role', '=', $jr)->get();
            //dd($app);
            //dd($company[0]->CTC);
            $student = Student::query()->select()->where('roll_no', '=', $roll)->get();
            // dd($student);
            if ($company[0]->CTC > 12) {
                $student[0]->attempts = "0";
                $student[0]->save();

            } else if ($company[0]->CTC > 7) {
                $student[0]->attempts = (string)number_format($student[0]->attempts)-1;
                $student[0]->save();
            } else {
                $student[0]->attempts = (string)number_format($student[0]->attempts)-1;
                $student[0]->save();
            }

            $app[0]->status = 'Selected';
            $app[0]->save();
            return back()->with('success', 'Updated');
        } else {
            $app = applications::query()->select()->where('id', '=', $rej)->get();
            $app[0]->status = 'Rejected';
            $app[0]->save();
            return back()->with('success', 'Updated');
        }
    }
}
