<?php

namespace Rabol\LivewireCalendar;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Class LivewireCalendar
 */
class LivewireCalendar extends Component
{
    /**
     *
     */
    public const CALENDAR_MODE_MONTH = 0;

    /**
     *
     */
    public const CALENDAR_MODE_WEEK = 1;

    /**
     * @var Carbon
     */
    public Carbon $startsAt;

    /**
     * @var Carbon
     */
    public Carbon $endsAt;

    /**
     * @var Carbon
     */
    public Carbon $gridStartsAt;

    /**
     * @var Carbon
     */
    public Carbon $gridEndsAt;

    /**
     * @var int
     */
    public int $weekStartsAt;

    /**
     * @var int
     */
    public int $weekEndsAt;

    /**
     * @var string
     */
    public string $calendarView;

    /**
     * @var string
     */
    public string $dayView;

    /**
     * @var string
     */
    public string $eventView;

    /**
     * @var string
     */
    public string $dayOfWeekView;

    /**
     * @var string
     */
    public string $dragAndDropClasses;

    /**
     * @var string|null
     */
    public ?string $beforeCalendarView;

    /**
     * @var string|null
     */
    public ?string $afterCalendarView;

    /**
     * @var int|null
     */
    public ?int $pollMillis;

    /**
     * @var string|null
     */
    public ?string $pollAction;

    /**
     * @var bool
     */
    public bool $dragAndDropEnabled;

    /**
     * @var bool
     */
    public bool $dayClickEnabled;

    /**
     * @var bool
     */
    public bool $eventClickEnabled;

    /**
     * @var int
     */
    public int $calendarMode;

    /**
     * @var string
     */
    public string $locale;

    /**
     * @var array|string[]
     */
    protected array $casts = [
        'startsAt' => 'date',
        'endsAt' => 'date',
        'gridStartsAt' => 'date',
        'gridEndsAt' => 'date',
    ];

    /**
     * @param $initialYear
     * @param $initialMonth
     * @param $initialWeek
     * @param $weekStartsAt
     * @param $calendarView
     * @param $dayView
     * @param $eventView
     * @param $dayOfWeekView
     * @param $dragAndDropClasses
     * @param $beforeCalendarView
     * @param $afterCalendarView
     * @param $pollMillis
     * @param $pollAction
     * @param $dragAndDropEnabled
     * @param $dayClickEnabled
     * @param $eventClickEnabled
     * @param $initialCalendarMode
     * @param $weekView
     * @param $initialLocale
     * @param $extras
     * @return void
     */
    public function mount($initialYear = null,
                          $initialMonth = null,
                          $initialWeek = null,
                          $weekStartsAt = null,
                          $calendarView = null,
                          $dayView = null,
                          $eventView = null,
                          $dayOfWeekView = null,
                          $dragAndDropClasses = null,
                          $beforeCalendarView = null,
                          $afterCalendarView = null,
                          $pollMillis = null,
                          $pollAction = null,
                          $dragAndDropEnabled = true,
                          $dayClickEnabled = true,
                          $eventClickEnabled = true,
                          $initialCalendarMode = 0,
                          $weekView = null,
                          $initialLocale = 'en',
                          $extras = [])
    {
        $this->weekStartsAt = $weekStartsAt ?? CarbonInterface::SUNDAY;
        $this->weekEndsAt = $this->weekStartsAt == CarbonInterface::SUNDAY
            ? CarbonInterface::SATURDAY
            : collect([0, 1, 2, 3, 4, 5, 6])->get($this->weekStartsAt + 6 - 7);

        $initialYear = $initialYear ?? Carbon::today()->locale($initialLocale)->year;
        $initialMonth = $initialMonth ?? Carbon::today()->locale($initialLocale)->month;
        $initialWeek = $initialWeek ?? Carbon::today()->locale($initialLocale)->week;

        if ($initialCalendarMode == self::CALENDAR_MODE_MONTH) {
            $this->startsAt = Carbon::createFromDate($initialYear, $initialMonth, 1)->locale($initialLocale)->startOfDay();
            $this->endsAt = $this->startsAt->clone()->endOfMonth()->startOfDay();
        } else {
            $this->startsAt = now()->locale($initialLocale)->year($initialYear)->month($initialMonth)->week($initialWeek)->startOfWeek($this->weekStartsAt)->startOfDay();
            $this->endsAt = $this->startsAt->clone()->endOfWeek()->endOfDay();
        }

        $this->calculateGridStartsEnds();

        if ($initialCalendarMode == self::CALENDAR_MODE_MONTH) {
            $this->setupViews($calendarView ?? 'livewire-calendar::calendar', $dayView, $eventView, $dayOfWeekView, $beforeCalendarView, $afterCalendarView);
        }

        if ($initialCalendarMode == self::CALENDAR_MODE_WEEK) {
            $this->setupViews($calendarView ?? 'livewire-calendar::week', $dayView, $eventView, $dayOfWeekView, $beforeCalendarView, $afterCalendarView);
        }

        $this->setupPoll($pollMillis, $pollAction);

        $this->dragAndDropEnabled = $dragAndDropEnabled;
        $this->dragAndDropClasses = $dragAndDropClasses ?? 'border border-blue-400 border-4';

        $this->dayClickEnabled = $dayClickEnabled;
        $this->eventClickEnabled = $eventClickEnabled;

        $this->calendarMode = $initialCalendarMode;

        $this->locale = $initialLocale;

        $this->afterMount($extras);
    }

    /**
     * @param string $locale
     * @return void
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param $extras
     * @return void
     */
    public function afterMount($extras = [])
    {
        //
    }

    /**
     * @param int $mode
     * @return void
     */
    public function setCalendarMode(int $mode)
    {
        $this->calendarMode = $mode;
    }

    /**
     * @param $calendarView
     * @param $dayView
     * @param $eventView
     * @param $dayOfWeekView
     * @param $beforeCalendarView
     * @param $afterCalendarView
     * @return void
     */
    public function setupViews($calendarView = null,
                               $dayView = null,
                               $eventView = null,
                               $dayOfWeekView = null,
                               $beforeCalendarView = null,
                               $afterCalendarView = null)
    {
        $this->calendarView = $calendarView ?? 'livewire-calendar::calendar';
        $this->dayView = $dayView ?? 'livewire-calendar::day';
        $this->eventView = $eventView ?? 'livewire-calendar::event';
        $this->dayOfWeekView = $dayOfWeekView ?? 'livewire-calendar::day-of-week';

        $this->beforeCalendarView = $beforeCalendarView ?? null;
        $this->afterCalendarView = $afterCalendarView ?? null;
    }

    /**
     * @param $pollMillis
     * @param $pollAction
     * @return void
     */
    public function setupPoll($pollMillis, $pollAction)
    {
        $this->pollMillis = $pollMillis;
        $this->pollAction = $pollAction;
    }

    /**
     * @return void
     */
    public function goToPreviousMonth()
    {
        $this->startsAt->subMonthNoOverflow();
        $this->endsAt->subMonthNoOverflow();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function goToNextMonth()
    {
        $this->startsAt->addMonthNoOverflow();
        $this->endsAt->addMonthNoOverflow();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function goToCurrentMonth()
    {
        $this->startsAt = Carbon::today()->startOfMonth()->startOfDay();
        $this->endsAt = $this->startsAt->clone()->endOfMonth()->startOfDay();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function goToPreviousWeek()
    {
        $this->startsAt->subWeek();
        $this->endsAt->subWeek();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function goToNextWeek()
    {
        $this->startsAt->addWeek();
        $this->endsAt->addWeek();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function goToCurrentWeek()
    {
        $this->startsAt = Carbon::today()->startOfWeek();
        $this->endsAt = $this->startsAt->clone()->endOfWeek();

        $this->calculateGridStartsEnds();
    }

    /**
     * @return void
     */
    public function calculateGridStartsEnds()
    {
        $this->gridStartsAt = $this->startsAt->clone()->startOfWeek($this->weekStartsAt);
        $this->gridEndsAt = $this->endsAt->clone()->endOfWeek($this->weekEndsAt);
    }

    /**
     * @throws Exception
     */
    public function monthGrid(): Collection
    {
        $firstDayOfGrid = $this->gridStartsAt;
        $lastDayOfGrid = $this->gridEndsAt;

        $numbersOfWeeks = ceil($lastDayOfGrid->diffInWeeks($firstDayOfGrid, true));
        $days = ceil($lastDayOfGrid->diffInDays($firstDayOfGrid, true));

        if ($days % 7 != 0) {
            throw new Exception('Livewire Calendar not correctly configured. Check initial inputs.');
        }

        $monthGrid = collect();
        $currentDay = $firstDayOfGrid->clone();

        while (! $currentDay->greaterThan($lastDayOfGrid)) {
            $monthGrid->push($currentDay->clone());
            $currentDay->addDay();
        }

        $monthGrid = $monthGrid->chunk(7);
        if ($numbersOfWeeks != $monthGrid->count()) {
            throw new Exception('Livewire Calendar calculated wrong number of weeks. Sorry :(');
        }

        return $monthGrid;
    }

    /**
     * @throws Exception
     */
    public function weekGrid(): Collection
    {
        $firstDayOfGrid = $this->gridStartsAt->clone()->startOfWeek($this->weekStartsAt);
        $lastDayOfGrid = $this->gridEndsAt->clone()->endOfWeek();

        $days = $lastDayOfGrid->diffInDays($firstDayOfGrid) + 1;

        if ($days != 7) {
            throw new Exception('Livewire Calendar not correctly configured. Check initial inputs.');
        }

        $weekGrid = collect();
        $currentDay = $firstDayOfGrid->clone();

        while (! $currentDay->greaterThan($lastDayOfGrid)) {
            $weekGrid->push($currentDay->clone());
            $currentDay->addDay();
        }

        return $weekGrid;
    }

    /**
     * @return Collection
     */
    public function events(): Collection
    {
        return collect();
    }

    /**
     * @param $day
     * @param Collection $events
     * @return Collection
     */
    public function getEventsForDay($day, Collection $events): Collection
    {
        return $events
            ->filter(function ($event) use ($day) {
                return Carbon::parse($event['date'])->isSameDay($day);
            });
    }

    /**
     * @param $year
     * @param $month
     * @param $day
     * @return void
     */
    public function onDayClick($year, $month, $day)
    {
        //
    }

    /**
     * @param $eventId
     * @return void
     */
    public function onEventClick($eventId)
    {
        //
    }

    /**
     * @param $eventId
     * @param $year
     * @param $month
     * @param $day
     * @return void
     */
    public function onEventDropped($eventId, $year, $month, $day)
    {
        //
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function render()
    {
        $events = $this->events();

        if ($this->calendarMode == self::CALENDAR_MODE_MONTH) {
            return view($this->calendarView)
                ->with([
                    'componentId' => $this->id,
                    'monthGrid' => $this->monthGrid(),
                    'events' => $events,
                    'getEventsForDay' => function ($day) use ($events) {
                        return $this->getEventsForDay($day, $events);
                    },
                ]);
        }

        if ($this->calendarMode == self::CALENDAR_MODE_WEEK) {
            return view($this->calendarView)
                ->with([
                    'componentId' => $this->getId(),
                    'weekGrid' => $this->weekGrid(),
                    'events' => $events,
                    'getEventsForDay' => function ($day) use ($events) {
                        return $this->getEventsForDay($day, $events);
                    },
                ]);
        }
    }
}
