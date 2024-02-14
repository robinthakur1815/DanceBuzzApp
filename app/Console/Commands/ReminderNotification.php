<?php

namespace App\Console\Commands;

use App\Collection;
use App\Enums\ClassPublishStatus;
use App\Enums\CollectionType;
use App\Enums\LiveClassStatus;
use App\Enums\PublishStatus;
use App\Enums\ReminderType;
use App\Enums\VendorClassStatus;
use App\Helpers\NotificationHelper;
use App\Model\Partner\PartnerClass;
use App\Model\Partner\PartnerLiveClassSchedule;
use App\Model\Partner\StudentRegistration;
use App\Model\ReminderNotificationSent;
use App\Order;
use App\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReminderNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send reminder notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('type');
        info('live class notification end '.$type);

        return true;
        $status = [LiveClassStatus::Active, LiveClassStatus::ReActivate, LiveClassStatus::ReSchedule];
        $startNow = now()->toDateTimeString();
        $endNow = now()->addHour()->toDateTimeString();
        if ($type == ReminderType::Classes) {
            info('class notification started');
            $classes = PartnerClass::whereBetween('start_time', [$startNow, $endNow])
                                    ->whereDate('start_date', now()->format('Y-m-d'))
                                    ->where('status', VendorClassStatus::Active)
                                    ->get();
            info([$classes]);
            foreach ($classes as $class) {
                $studentRegistrations = StudentRegistration::where('vendorclass_id', $class->id)->pluck('student_id');
                if (count($studentRegistrations)) {
                    $collection = Collection::where('vendor_class_id', $class->id)->whereNotNull('published_content')
                                            ->where('status', PublishStatus::Published)->first();

                    if ($collection) {
                        $sentUsersIds = ReminderNotificationSent::where('collection_id', $collection->id)->pluck('user_id');
                        $userIds = [];
                        if (count($sentUsersIds)) {
                            $userIds = Student::whereIn('id', $studentRegistrations)->whereNotIn('user_id', $sentUsersIds)->pluck('user_id');
                        } else {
                            $userIds = Student::whereIn('id', $studentRegistrations)->pluck('user_id');
                        }
                        if (count($userIds)) {
                            $this->insertSentNotification($collection, $userIds);
                            NotificationHelper::reminderNotification($collection, $userIds);
                        }
                    }
                }
            }

            info('class notification end');
        }

        if ($type == ReminderType::LiveClass) {
            info('live class notification started');

            $classesIds = PartnerLiveClassSchedule::whereBetween('start_date_time', [$startNow, $endNow])
                                        ->whereIn('status', $status)
                                        ->whereDate('start_date_time', now()->format('Y-m-d'))
                                        ->whereNotNull('class_id')
                                        ->pluck('class_id');

            $classes = [];
            if (count($classesIds)) {
                $classes = PartnerClass::where('status', VendorClassStatus::Active)
                ->whereIn('id', $classesIds)
                ->where('publish_status', ClassPublishStatus::Published)
                ->get();
            }

            foreach ($classes as $class) {
                $studentRegistrations = StudentRegistration::where('vendorclass_id', $class->id)->pluck('student_id');

                $collection = Collection::where('vendor_class_id', $class->id)->whereNotNull('published_content')
                                        ->where('status', PublishStatus::Published)->first();
                if ($collection and count($studentRegistrations)) {
                    $sentUsersIds = ReminderNotificationSent::where('collection_id', $collection->id)
                    ->whereDate('created_at', now()->format('Y-m-d'))->pluck('user_id');
                    $userIds = [];
                    if (count($sentUsersIds)) {
                        $userIds = Student::whereIn('id', $studentRegistrations)->whereNotIn('user_id', $sentUsersIds)->pluck('user_id');
                    } else {
                        $userIds = Student::whereIn('id', $studentRegistrations)->pluck('user_id');
                    }

                    if (count($userIds)) {
                        $dateTime = $this->startDateTime($class);
                        $this->insertSentNotification($collection, $userIds);
                        NotificationHelper::reminderNotification($collection, $userIds, $dateTime);
                    }
                }
            }

            info('live class notification end');
        }

        if ($type == ReminderType::WorkshopEvents) {
            info('workshop notification started');

            $startDate = now()->subDay()->format('Y/m/d');
            $startTime = now()->subHours(2)->toDateTimeString();
            $types = [CollectionType::workshops, CollectionType::events];
            $collections = Collection::whereNotNull('published_content')
                                        ->where(function ($q) use ($startDate, $startTime) {
                                            $q->where('published_content->start_date', $startDate)
                                            ->where('published_content->start_time', '<=', $startTime);
                                        })->where('status', PublishStatus::Published)
                                        ->whereIn('collection_type', $types)
                                        ->has('confirmOrders')
                                        ->get();

            foreach ($collections as $collection) {
                $sentUsersIds = ReminderNotificationSent::where('collection_id', $class->id)->pluck('user_id');
                $userIds = [];
                if (count($sentUsersIds)) {
                    $userIds = Order::where('collection_id', $collection->id)->whereNotIn('purchaser_id', $sentUsersIds)->pluck('purchaser_id');
                } else {
                    $userIds = Order::where('collection_id', $collection->id)->pluck('purchaser_id');
                }

                if (count($userIds)) {
                    $this->insertSentNotification($collection, $userIds);
                    NotificationHelper::reminderNotification($collection, $userIds);
                }
            }

            info('workshop notification end');
        }
    }

    private function insertSentNotification($collection, $userIds = [])
    {
        $data = [];
        foreach ($userIds as $userId) {
            $data[] = [
                'user_id'       => $userId,
                'collection_id' => $collection->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if (count($data)) {
            DB::connection('mysql2')->table('reminder_notification_sents')->insert($data);
        }
    }

    private function startDateTime($class)
    {
        $date = now()->format('M d, Y');
        if ($class->start_time) {
            $date = $date.' at '.$class->start_time->format('h:i A');
        }

        return $date;
    }
}
