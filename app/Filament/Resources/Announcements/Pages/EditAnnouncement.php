<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    /**
     * One that is out, or was sent back, is offered to the account of a department as "Send for approval":
     * that is what saving it will do.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (Announcement::waitsForApproval(auth()->user()) && in_array($data['status'] ?? null, [Announcement::STATUS_PUBLISHED, Announcement::STATUS_RETURNED], true)) {
            $data['status'] = Announcement::STATUS_PENDING;
        }

        // Whoever posts for the whole hospital and opens one that was sent back sees it as a draft: theirs to publish or keep.
        if (! Announcement::waitsForApproval(auth()->user()) && in_array($data['status'] ?? null, [Announcement::STATUS_PENDING, Announcement::STATUS_RETURNED], true)) {
            $data['status'] = Announcement::STATUS_DRAFT;
        }

        return $data;
    }

    /** The account of a department cannot hand what it wrote to another department. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()->postsForOneDepartment()) {
            $data['department_id'] = auth()->user()->department_id;
        }

        // While the hospital asks to see it first, the account of a department cannot put its own writing out:
        // anything but a draft waits for approval, a change to one already out as well.
        if (Announcement::waitsForApproval(auth()->user()) && ($data['status'] ?? null) !== Announcement::STATUS_DRAFT) {
            $data['status'] = Announcement::STATUS_PENDING;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // What is saved, as a visitor would see it; staff who are signed in can open one that is not out yet.
            Action::make('preview')
                ->label(__('Preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn () => route('announcements.show', $this->getRecord()))
                ->openUrlInNewTab(),
            // A QR code that leads to the post, to put on the printed poster.
            Action::make('qr')
                ->label(__('QR code'))
                ->icon(Heroicon::OutlinedQrCode)
                ->color('gray')
                ->modalHeading(__('QR code for a poster'))
                ->modalDescription(__('Scanning it opens this post on the website. It prints sharp at any size.'))
                ->modalContent(fn () => view('filament.qr', ['url' => route('qr', ['to' => '/'.ltrim((string) parse_url(route('announcements.show', $this->getRecord()), PHP_URL_PATH), '/')])]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close')),
            DeleteAction::make(),
        ];
    }
}
