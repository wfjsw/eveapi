<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018, 2019  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Eveapi\Jobs\Contracts\Character;

use Seat\Eveapi\Jobs\EsiBase;
use Seat\Eveapi\Models\Contracts\CharacterContract;
use Seat\Eveapi\Models\Contracts\ContractDetail;

/**
 * Class Contracts.
 * @package Seat\Eveapi\Jobs\Contracts\Character
 */
class Contracts extends EsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/contracts/';

    /**
     * @var string
     */
    protected $version = 'v1';

    /**
     * @var string
     */
    protected $scope = 'esi-contracts.read_character_contracts.v1';

    /**
     * @var array
     */
    protected $tags = ['character', 'contracts'];

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle()
    {

        if (! $this->preflighted()) return;

        while (true) {

            $contracts = $this->retrieve([
                'character_id' => $this->getCharacterId(),
            ]);

            if ($contracts->isCachedLoad()) return;

            collect($contracts)->each(function ($contract) {

                // Update or create the contract details.
                ContractDetail::firstOrNew([
                    'contract_id' => $contract->contract_id,
                ])->fill([
                    'issuer_id'             => $contract->issuer_id,
                    'issuer_corporation_id' => $contract->issuer_corporation_id,
                    'assignee_id'           => $contract->assignee_id,
                    'acceptor_id'           => $contract->acceptor_id,
                    'start_location_id'     => isset($contract->start_location_id) ? $contract->start_location_id : null,
                    'end_location_id'       => isset($contract->end_location_id) ? $contract->end_location_id : null,
                    'type'                  => $contract->type,
                    'status'                => $contract->status,
                    'title'                 => isset($contract->title) ? $contract->title : null,
                    'for_corporation'       => $contract->for_corporation,
                    'availability'          => $contract->availability,
                    'date_issued'           => carbon($contract->date_issued),
                    'date_expired'          => carbon($contract->date_expired),
                    'date_accepted'         => isset($contract->date_accepted) ?
                        carbon($contract->date_accepted) : null,
                    'days_to_complete'      => isset($contract->days_to_complete) ? $contract->days_to_complete : null,
                    'date_completed'        => isset($contract->date_completed) ?
                        carbon($contract->date_completed) : null,
                    'price'                 => isset($contract->price) ? $contract->price : null,
                    'reward'                => isset($contract->reward) ? $contract->reward : null,
                    'collateral'            => isset($contract->collateral) ? $contract->collateral : null,
                    'buyout'                => isset($contract->buyout) ? $contract->buyout : null,
                    'volume'                => isset($contract->volume) ? $contract->volume : null,
                ])->save();

                // Ensure the character is associated to this contract
                CharacterContract::firstOrCreate([
                    'character_id' => $this->getCharacterId(),
                    'contract_id'  => $contract->contract_id,
                ]);
            });

            if (! $this->nextPage($contracts->pages))
                break;
        }
    }
}
