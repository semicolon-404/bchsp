<?php

namespace App\Support;

use App\Models\WorkGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Who runs the hospital and how it is organised, as the pages under "About us" show it: the director and the
 * year it was founded (Site settings > Organisation), and the groups of work with their heads (work_groups).
 */
class Organisation
{
    /** The titles before a Thai name, longest first, so that "นางสาว" is not read as "นาง". */
    private const TITLES = ['นางสาว', 'นาย', 'นาง', 'น.ส.', 'น.พ.', 'นพ.', 'พ.ญ.', 'พญ.', 'ท.พ.', 'ทพ.', 'ท.พ.ญ.', 'ทพญ.', 'ภก.', 'ภญ.', 'ดร.', 'Mr.', 'Mrs.', 'Ms.', 'Dr.'];

    /**
     * The director, or null while no name has been given.
     *
     * @return array{name: string, title: string, photo: ?string}|null
     */
    public static function director(): ?array
    {
        $name = trim((string) SiteSettings::get('org.director_name'));

        if ($name === '') {
            return null;
        }

        $photo = SiteSettings::get('org.director_photo');

        return [
            'name' => $name,
            'title' => trim((string) SiteSettings::get('org.director_title')) ?: __('Director of :name', ['name' => SiteSettings::name()]),
            'photo' => is_string($photo) && $photo !== '' ? Storage::disk('public')->url($photo) : null,
        ];
    }

    /** The name of the mission group every group of work stands under, when the hospital has one. */
    public static function missionGroup(): ?string
    {
        return trim((string) SiteSettings::get('org.mission_group')) ?: null;
    }

    /** The day the hospital was founded, or null. */
    public static function foundedOn(): ?Carbon
    {
        $day = SiteSettings::get('org.founded_on');

        try {
            return filled($day) ? Carbon::parse((string) $day)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** How many whole years it has been at work. */
    public static function years(): ?int
    {
        $founded = self::foundedOn();

        return $founded && $founded->isPast() ? (int) $founded->diffInYears(today()) : null;
    }

    /** @return Collection<int, WorkGroup> */
    public static function groups(): Collection
    {
        return WorkGroup::shown()->with('department')->get();
    }

    /**
     * The figures of the organisation, each null while it is not known: the groups of work, the people in
     * them (or the number an administrator gave for the whole hospital, which counts those outside any group
     * too), and the doctors and dentists.
     *
     * @return array{groups: int, people: ?int, doctors: ?int}
     */
    public static function figures(): array
    {
        $groups = self::groups();
        $counted = (int) $groups->sum('staff_count');
        $given = (int) SiteSettings::get('org.staff_total');
        $doctors = (int) SiteSettings::get('org.doctors');

        return [
            'groups' => $groups->count(),
            'people' => max($given, $counted) ?: null,
            'doctors' => $doctors ?: null,
        ];
    }

    /**
     * The heads of the groups of work, one entry to a person: someone who heads two groups is listed once,
     * with both. In the order of the groups.
     *
     * @return list<array{name: string, position: ?string, photo: ?string, groups: list<WorkGroup>}>
     */
    public static function heads(): array
    {
        $people = [];

        foreach (self::groups() as $group) {
            $name = trim((string) $group->head_name);

            if ($name === '') {
                continue;
            }

            $key = self::bareName($name);
            $people[$key] ??= ['name' => $name, 'position' => $group->head_position ?: null, 'photo' => null, 'groups' => []];
            $people[$key]['photo'] ??= $group->headPhotoUrl();
            $people[$key]['position'] ??= $group->head_position ?: null;
            $people[$key]['groups'][] = $group;
        }

        return array_values($people);
    }

    /** A name without the title before it: "น.พ.สมชาย ใจดี" and "นายสมชาย ใจดี" are the same person. */
    public static function bareName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        foreach (self::TITLES as $title) {
            if (str_starts_with($name, $title)) {
                return trim(mb_substr($name, mb_strlen($title)));
            }
        }

        return $name;
    }

    /** The letter that stands for a person who has no photograph: the first of the name itself, not of the title. */
    public static function initial(string $name): string
    {
        // A Thai vowel written before its consonant is not what the name is known by.
        $bare = (string) preg_replace('/^[เแโใไ\s]+/u', '', self::bareName($name)) ?: self::bareName($name);

        return mb_strtoupper(mb_substr($bare, 0, 1)) ?: '?';
    }
}
