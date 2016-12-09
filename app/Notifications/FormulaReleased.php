<?php

namespace App\Notifications;

use App\Formula;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class FormulaReleased extends Notification
{
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function via($notifiable)
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
        return (new SlackMessage)
            ->success()
            ->content('One of monitored formulas has been released!')
            ->attachment(function (SlackAttachment $attachment) use ($formula) {
                $attachment
                    ->title($formula->getAttribute('name'), $formula->getAttribute('url'))
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
        $hash = explode(':', $formula->getAttribute('hash'));

        return [
            'Version' => $formula->getAttribute('version'),
            'Checked at' => $formula->getAttribute('checked_at')->toDateTimeString(),
            'Archive url' => $formula->getAttribute('archive'),
            $hash[0] => $hash[1],
        ];
    }
}
