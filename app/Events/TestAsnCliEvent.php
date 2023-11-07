<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedTheme;

class TestAsnCliEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private AnsiToHtmlConverter $converter;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $commandOutput)
    {
        //        $theme = new SolarizedTheme();
        //        $this->converter = new AnsiToHtmlConverter(null, false);
    }

    public function broadcastWith(): array
    {
        // Convert the output from ASCII to HTML entities.
        //        $this->commandOutput = $this->converter->convert($this->commandOutput);
        //        $this->commandOutput = html_entity_decode($this->commandOutput, ENT_COMPAT, 'UTF-8');
        return [
            'commandOutput' => $this->commandOutput,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('asn-cli'),
        ];
    }
}
