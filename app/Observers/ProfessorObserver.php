<?php

namespace App\Observers;

use App\Models\Professors\Notification;
use App\Models\Professors\Professor;

class ProfessorObserver
{
    /**
     * Handle the Professor "created" event.
     */

    public function saved(Professor $professor): void
    {
//        $checks = [
//            'languages'      => 'حداقل یک زبان انتخاب کنید.',
//            'languageLevels' => 'حداقل یک سطح زبان مشخص کنید.',
//            'ageGroups'      => 'حداقل یک گروه سنی انتخاب کنید.',
//            'learningGoals'  => 'حداقل یک هدف یادگیری تعیین کنید.',
//            'ClassPrice'     => 'قیمت کلاس خصوصی را مشخص کنید.',
//        ];
//
//        foreach ($checks as $relation => $message) {
//            $hasRelation = $professor->$relation()->exists();
//
//            if ($relation === 'ClassPrice') {
//                $hasRelation = $professor->ClassPrice()
//                    ->where('class_type', 'private')
//                    ->exists();
//            }
//
//            if (! $hasRelation) {
//                Notification::firstOrCreate([
//                    'user_id' => $professor->user_id,
//                    'text'    => $message,
//                ]);
//            } else {
//                Notification::where('user_id', $professor->user_id)
//                    ->where('text', $message)
//                    ->delete();
//            }
//        }
    }
    public function created(Professor $professor): void
    {
        //
    }

    /**
     * Handle the Professor "updated" event.
     */
    public function updated(Professor $professor): void
    {
        //
    }

    /**
     * Handle the Professor "deleted" event.
     */
    public function deleted(Professor $professor): void
    {
        //
    }

    /**
     * Handle the Professor "restored" event.
     */
    public function restored(Professor $professor): void
    {
        //
    }

    /**
     * Handle the Professor "force deleted" event.
     */
    public function forceDeleted(Professor $professor): void
    {
        //
    }
}
