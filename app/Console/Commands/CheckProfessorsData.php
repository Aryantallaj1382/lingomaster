<?php

namespace App\Console\Commands;

use App\Models\Professors\Notification;
use App\Models\Professors\Professor;
use Illuminate\Console\Command;

class CheckProfessorsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-professors-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $professors = Professor::with(['languages', 'ageGroupLanguageLevels', 'ageGroups', 'learningGoals', 'ClassPrice'])->get();

        foreach ($professors as $professor) {
            $checks = [
                'languages'      => 'حداقل یک زبان انتخاب کنید.',
                'ageGroupLanguageLevels' => 'حداقل یک سطح زبان مشخص کنید.',
                'ageGroups'      => 'حداقل یک گروه سنی انتخاب کنید.',
                'learningGoals'  => 'حداقل یک هدف از آموزش تعیین کنید.',
                'ClassPrice'     => 'قیمت کلاس خصوصی را مشخص کنید.',
            ];

            foreach ($checks as $relation => $message) {
                $hasRelation = $professor->$relation()->exists();

                if ($relation === 'ClassPrice') {
                    $hasRelation = $professor->ClassPrice()
                        ->where('class_type', 'private')
                        ->exists();
                }

                if (! $hasRelation) {
                    $notif = Notification::where('user_id', $professor->user_id)
                        ->where('text', $message)
                        ->first();

                    if ($notif) {
                        $notif->update(['is_seen' => false]);
                    } else {
                        Notification::create([
                            'user_id' => $professor->user_id,
                            'text'    => $message,
                            'is_seen' => false,
                        ]);
                    }
                }else {
                    Notification::where('user_id', $professor->user_id)
                        ->where('text', $message)
                        ->delete();
                }
            }
        }

        $this->info('Checked professors data and updated notifications.');
    }
}
