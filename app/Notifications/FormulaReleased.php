<?php

namespace App\Notifications;

use App\Models\Formula;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class FormulaReleased extends Notification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $formula
     *
     * @return SlackMessage
     */
    public function toSlack(Formula $formula): SlackMessage
    {
        $formula = $formula->fresh();

        return (new SlackMessage)
            ->success()
            ->content('Formula New Release!')
            ->attachment(function (SlackAttachment $attachment) use ($formula) {
                $attachment
                    ->title($formula->name, 'https://github.com/'.$formula->repo)
                    ->fields($this->fields($formula));
            });
    }

    /**
     * Get the fields of the attachment.
     *
     * @param Formula $formula
     *
     * @return array
     */
    protected function fields(Formula $formula): array
    {
        return [
            'Version' => $formula->version,
            'Checked at' => $formula->checked_at->toDateTimeString(),
            'Last PR' => $formula->pull_request,
        ];
    }
}
