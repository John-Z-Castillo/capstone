<?php

namespace App\lib;

use DateTime;

class Time {

    /**
     * Create a date with the starting day set to 1.
     * @param string date
     * @return DateTime
     */
    static function startMonth($date) {
        return DateTime::createFromFormat('Y-m-d', $date . '-01');
    }

    /**
     * Create a date with the starting day set to the last day.
     * @param string date
     * @return DateTime
     */
    static function endMonth($date) {
        $date = self::startMonth($date);
        $date =  $date->format('Y-m-t');
        return DateTime::createFromFormat('Y-m-d', $date);
    }

    static function convert($date) {
        return $date->format('Y-m-d');
    }
    
    /**
     * Create a timestamp at now time
     */
    static function timestamp() {
        return DateTime::createFromFormat('U', time());
    }

       // return payment amount for this month
       static function thisMonth()
       {
           return date("Y-m-01");
       }
   
       // return payment amount for next month
       static function nextMonth()
       {
           return date("Y-m-01", strtotime("+1 month", strtotime(self::thisMonth())));
       }
   
       // return months
       static function getMonths($startMonth, $endMonth)
       {
   
           // Create DateTime objects for the start and end months
           $startDateTime = DateTime::createFromFormat('Y-m-01', $startMonth);
           $endDateTime = DateTime::createFromFormat('Y-m-01', $endMonth);
   
           // Initialize an empty array to store the months
           $months = [];
   
           // Loop through the months and add them to the array
           while ($startDateTime <= $endDateTime) {
               $months[] = $startDateTime->format('Y-m-01');
               $startDateTime->modify('+1 month'); // Add 1 month
           }
   
           return $months;
       }
}
