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
    public function via()
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
    public function toSlack(Formula $formula)
    {
        $formula = $formula->fresh();

        return (new SlackMessage)
            ->success()
            ->content('Formula New Release!')
            ->attachment(function (SlackAttachment $attachment) use ($formula) {
                $attachment
                    ->title($formula->getAttribute('name'), 'https://github.com/'.$formula->getAttribute('repo'))
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
    protected function fields(Formula $formula)
    {
        return [
            'Version' => $formula->getAttribute('version'),
            'Checked at' => $formula->getAttribute('checked_at')->toDateTimeString(),
            'Last PR' => $formula->getAttribute('pull_request'),
        ];
    }
}
