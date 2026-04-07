<?php

namespace App\Facades;

use App\Knowledge\KnowledgeAskGateway;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Knowledge\KnowledgeAskBuilder ask(string $question)
 *
 * @see KnowledgeAskGateway
 */
class Knowledge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return KnowledgeAskGateway::class;
    }
}
