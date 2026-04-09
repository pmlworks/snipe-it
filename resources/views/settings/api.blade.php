@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/settings/general.oauth_title') }}
    @parent
@stop


{{-- Page content --}}
@section('content')
    @if (!config('app.lock_passwords'))
        <x-container>
                <x-tabs>
                    <x-slot:tabnav>

                        <x-tabs.nav-item
                            name="personal-access-tokens"
                            :label="trans('admin/settings/general.oauth_personal_access_tokens')"
                            :count="($personalAccessTokens ?? collect())->count()"
                        />

                        <x-tabs.nav-item
                            name="oauth-clients"
                            :label="trans('admin/settings/general.oauth_clients')"
                        />

                        <x-tabs.nav-item
                            name="authorized-applications"
                            :label="trans('admin/settings/general.oauth_authorized_apps')"
                        />
                    </x-slot:tabnav>

                    <x-slot:tabpanes>
                        <x-tabs.pane name="authorized-applications">
                            <div class="oauth-tab-content">
                                <livewire:oauth-clients section="authorized-applications"/>
                            </div>
                        </x-tabs.pane>

                        <x-tabs.pane name="personal-access-tokens">
                            @if(($personalAccessTokens ?? collect())->isEmpty())
                                <p class="text-muted">{{ trans('admin/settings/general.oauth_personal_access_tokens_none') }}</p>
                            @else
                                <div id="PersonalAccessTokensToolbar" class="pull-left" style="min-width: 280px; padding-top: 10px;"></div>
                                <div class="table-responsive">
                                    <table
                                        class="table table-striped snipe-table"
                                        data-toolbar="#PersonalAccessTokensToolbar"
                                        data-toggle="table"
                                        data-sort-name="created_at"
                                        data-sort-order="desc"
                                    >
                                        <thead>
                                            <tr>
                                                <th data-field="name" data-sortable="true">{{ trans('general.name') }}</th>
                                                <th data-field="user" data-sortable="true">{{ trans('general.created_by') }}</th>
                                                <th data-field="client" data-sortable="true">{{ trans('admin/settings/general.oauth_client') }}</th>
                                                <th data-field="status" data-sortable="true">{{ trans('general.status') }}</th>
                                                <th data-field="created_at" data-sortable="true">{{ trans('general.created_at') }}</th>
                                                <th data-field="expires_at" data-sortable="true">{{ trans('general.expires') }}</th>
                                                <th data-field="actions" data-sortable="false">
                                                    <span class="sr-only">{{ trans('general.actions') }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($personalAccessTokens as $token)
                                                <tr>
                                                    <td>{{ $token->name ?: $token->id }}</td>
                                                    <td>
                                                        @if($token->existing_user_id)
                                                            @php
                                                                $profileLabel = $token->display_name ?: $token->username;
                                                                $isSoftDeletedUser = $token->user_deleted_at !== null;
                                                            @endphp
                                                            <a href="{{ route('users.show', $token->token_user_id) }}">
                                                                @if($isSoftDeletedUser)
                                                                    <del>{{ $profileLabel }}</del>
                                                                @else
                                                                    {{ $profileLabel }}
                                                                @endif
                                                            </a>
                                                        @else
                                                            {{ trans('admin/settings/general.oauth_deleted_user', ['id' => $token->token_user_id]) }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $token->client_name }}</td>
                                                    <td>
                                                        @if((int) $token->revoked === 1)
                                                            <span class="label label-danger">{{ trans('admin/settings/general.oauth_token_status_revoked') }}</span>
                                                        @else
                                                            <span class="label label-success">{{ trans('admin/settings/general.oauth_token_status_active') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $token->created_at ? \App\Helpers\Helper::getFormattedDateObject($token->created_at, 'datetime', false) : '' }}</td>
                                                    <td>{{ $token->expires_at ? \App\Helpers\Helper::getFormattedDateObject($token->expires_at, 'datetime', false) : '' }}</td>
                                                    <td class="text-right">
                                                        @if((int) $token->revoked === 1)
                                                            <form method="POST" action="{{ route('settings.oauth.tokens.unrevoke', ['token' => $token->id]) }}" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-default text-success" data-tooltip="true" title="{{ trans('admin/settings/general.oauth_unrevoke') }}">
                                                                    <i class="fa-solid fa-toggle-off"></i></button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('settings.oauth.tokens.revoke', ['token' => $token->id]) }}" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-default text-danger" data-tooltip="true" title="{{ trans('admin/settings/general.oauth_revoke') }}">
                                                                    <i class="fa-solid fa-toggle-on"></i></button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </x-tabs.pane>

                        <x-tabs.pane name="oauth-clients">
                            <div class="oauth-tab-content">
                                <livewire:oauth-clients section="oauth-clients"/>
                            </div>
                        </x-tabs.pane>
                    </x-slot:tabpanes>
                </x-tabs>
        </x-container>
    @else
        <p class="text-warning"><i class="fas fa-lock" aria-hidden="true"></i> {{ trans('general.feature_disabled') }}</p>
    @endif

@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table', ['simple_view' => true])
@endsection
