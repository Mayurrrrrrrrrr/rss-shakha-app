<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $shakha = DB::table('shakhas')->where('id', $user['shakha_id'])->first();
        
        return $this->view('dashboard', [
            'user' => $user,
            'shakha' => $shakha,
            'pageTitle' => 'डैशबोर्ड (Dashboard)'
        ]);
    }
}
