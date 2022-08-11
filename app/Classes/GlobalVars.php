<?php

namespace App\Classes;

class GlobalVars
{

    const DASHBOARD_URL = "/myaccount/dashboard";

    const LOAN_STATUS = [

        'pending' => 'Pending',
        'cancelled' => 'Cancelled',
        'declined' => 'Declined',
        'approved' => 'Approved',
        'authorized' => 'Authorized',
        'awaiting_disbursal' => 'Awaiting Disbursal',
        'disbursed' => 'Disbursed',
        'running' => 'Running',
        'tenor_overdue' => 'Tenor Overdue',
        'overdue' => 'Overdue',
        'completed' => 'Completed',

    ];

    const QUALIFICATIONS = [
        'nil' => 'NIL',
        'ssce' => 'SSCE',
        'nd' => 'ND',
        'hnd' => 'HND',
        'bsc' => 'BSc',
        'masters' => 'Masters',
        'phd' => 'PHD',
        'undergraduate' => 'Undergraduate',
        'mbbs' => 'MBBS',
        'b_pharm' => 'B.Pharm',
        'b_eng' => 'B.Eng',
        'b_art' => 'B.Arts',
        'professor' => 'Professor',
        'llb' => 'LLB',
    ];

    const PAYMENT_METHODS = [
        'processor' => 'Processor',
        'bank' => "Bank",
        'transfer' => 'Transfer',
        'cash' => 'Cash',
        'others' => 'Others',
    ];

    const EMPLOYMENT_STATUS = [
        'self_employed' => 'Self Employed',
        'employed' => 'Employed',
        'unemployed' => 'Unemployed',
    ];

    const STATES = [
        "Abia",
        "Abuja",
        "Adamawa",
        "Akwa Ibom",
        "Anambra",
        "Bauchi",
        "Bayelsa",
        "Benue",
        "Borno",
        "Cross River",
        "Delta",
        "Ebonyi",
        "Edo",
        "Ekiti",
        "Enugu",
        "FCT",
        "Gombe",
        "Imo",
        "Jigawa",
        "Kaduna",
        "Kano",
        "Katsina",
        "Kebbi",
        "Kogi",
        "Kwara",
        "Lagos",
        "Nasarawa",
        "Niger",
        "Ogun",
        "Ondo",
        "Osun",
        "Oyo",
        "Plateau",
        "Rivers",
        "Sokoto",
        "Taraba",
        "Yobe",
        "Zamfara",
    ];

    const WAIVER_REASONS = [
        'earlier_payment' => 'Borrower made payment earlier than recorded',
        'bad_debt' => "Balance written-off/Bad Debt",
        'account_balance_error' => 'Account Balance Error',
    ];

    const LOAN_AMOUNTS = [
        5000, 10000, 15000,
    ];

    const LOAN_DURATIONS = [
        10, 15, 30,
    ];

    const LOAN_PURPOSES = [
        "SALARY ADVANCE", "BUSINESS",
        "SCHOOL FEES", "FAMILY UPKEEP",
        "HOUSEHOLD GOODS", "MEDICAL EXPENSES",
        "VACATION", "EMERGENCY",
        "BILLS", "TRAVELS",
        "MISCELLANEOUS", "OTHERS",
    ];

    const NAIRA = '₦';

    public static function getAll()
    {

        $meta = new \StdClass;

        $meta->loanStatuses = Self::LOAN_STATUS;
        $meta->dashboardUrl = Self::DASHBOARD_URL;
        // $meta->loanAmounts  = Self::$loanAmounts;
        $meta->loanPurposes = Self::LOAN_PURPOSES;
        $meta->n = Self::NAIRA;
        $meta->qualifications = Self::QUALIFICATIONS;
        $meta->paymentMethods = Self::PAYMENT_METHODS;
        $meta->waiverReasons = Self::WAIVER_REASONS;
        $meta->loan_durations = Self::LOAN_DURATIONS;

        return $meta;

    }

    /**
     * Get a list of states in key value pair used for select dropdown
     *
     * @return array
     */
    public static function getStateList(): array
    {

        $s = [];

        foreach (Self::STATES as $state) {
            $s[mb_strtolower($state)] = $state;

        }

        return $s;

    }

}
