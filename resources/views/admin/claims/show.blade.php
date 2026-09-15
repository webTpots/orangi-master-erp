<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.claims.index') }}" class="text-content-muted hover:text-content transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h2 class="text-xl font-display font-semibold text-content">Claim <span class="font-mono text-base">CLM-{{ str_pad($claim->id, 5, '0', STR_PAD_LEFT) }}</span></h2>
                @php
                    $statusColors = [
                        'draft' => 'bg-neutral-50 text-neutral-500',
                        'filed' => 'bg-brand-50 text-brand-600',
                        'under_review' => 'bg-warning-50 text-warning-600',
                        'approved' => 'bg-success-50 text-success-500',
                        'partially_approved' => 'bg-warning-50 text-warning-700',
                        'rejected' => 'bg-danger-50 text-danger-500',
                        'settled' => 'bg-success-50 text-success-600',
                        'closed' => 'bg-neutral-50 text-neutral-500',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$claim->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $claim->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if ($claim->status === 'draft')
                    <form method="POST" action="{{ route('admin.claims.file', $claim) }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('File this claim?')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-600 px-3 py-1.5 text-sm font-medium transition">
                            File Claim
                        </button>
                    </form>
                @endif

                @foreach ($availableTransitions as $transition)
                    @if ($transition !== 'filed' || $claim->status !== 'draft')
                        @if (in_array($transition, ['approved', 'partially_approved']))
                            {{-- These need a form with approved_amount --}}
                        @elseif ($transition === 'settled')
                            {{-- Settle needs its own form --}}
                        @else
                            <form method="POST" action="{{ route('admin.claims.update-status', $claim) }}" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ $transition }}">
                                <button type="submit" onclick="return confirm('Change status to {{ \App\Models\Claim::STATUS_LABELS[$transition] ?? ucfirst($transition) }}?')"
                                        class="inline-flex items-center gap-1.5 rounded-lg {{ $transition === 'rejected' ? 'border border-danger-500 text-danger-500 hover:bg-danger-50' : 'border border-surface-border text-content hover:bg-surface-secondary' }} px-3 py-1.5 text-sm font-medium transition">
                                    {{ \App\Models\Claim::STATUS_LABELS[$transition] ?? ucfirst($transition) }}
                                </button>
                            </form>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Claim Details --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Claim Details</h3>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div><dt class="text-content-muted">Claim Type</dt><dd class="font-medium text-content">{{ $claim->type_label }}</dd></div>
                            <div><dt class="text-content-muted">Claim Against</dt><dd class="font-medium text-content">{{ $claim->against_label }}</dd></div>
                            <div><dt class="text-content-muted">Reference #</dt><dd class="font-mono font-medium text-content">{{ $claim->reference_number ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Currency</dt><dd class="font-medium text-content">{{ $claim->currency }}</dd></div>
                        </dl>
                        @if ($claim->notes)
                            <div class="mt-4 pt-4 border-t border-surface-border">
                                <dt class="text-xs text-content-muted mb-1">Notes</dt>
                                <dd class="text-sm text-content">{{ $claim->notes }}</dd>
                            </div>
                        @endif
                    </div>

                    {{-- Amounts Card --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Financial Summary</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center p-4 rounded-lg bg-surface-secondary">
                                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Claimed</p>
                                <p class="mt-1 text-xl font-semibold text-content">Rs. {{ number_format($claim->claimed_amount, 2) }}</p>
                            </div>
                            <div class="text-center p-4 rounded-lg bg-surface-secondary">
                                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Approved</p>
                                <p class="mt-1 text-xl font-semibold {{ $claim->approved_amount ? 'text-success-500' : 'text-content-muted' }}">
                                    {{ $claim->approved_amount ? 'Rs. ' . number_format($claim->approved_amount, 2) : '-' }}
                                </p>
                            </div>
                            <div class="text-center p-4 rounded-lg bg-surface-secondary">
                                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Settled</p>
                                <p class="mt-1 text-xl font-semibold {{ $claim->settled_amount ? 'text-success-600' : 'text-content-muted' }}">
                                    {{ $claim->settled_amount ? 'Rs. ' . number_format($claim->settled_amount, 2) : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Approve / Settle Forms --}}
                    @if (in_array('approved', $availableTransitions) || in_array('partially_approved', $availableTransitions))
                        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Approve Claim</h3>
                            <form method="POST" action="{{ route('admin.claims.update-status', $claim) }}" class="space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-content">Approved Amount</label>
                                        <input type="number" name="approved_amount" step="0.01" min="0" value="{{ old('approved_amount', $claim->claimed_amount) }}"
                                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-content">Status</label>
                                        <select name="status" class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            @if (in_array('approved', $availableTransitions))
                                                <option value="approved">Approved (Full)</option>
                                            @endif
                                            @if (in_array('partially_approved', $availableTransitions))
                                                <option value="partially_approved">Partially Approved</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-content">Resolution Notes</label>
                                    <textarea name="resolution_notes" rows="2"
                                              class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                                              placeholder="Notes about the approval...">{{ old('resolution_notes') }}</textarea>
                                </div>
                                <button type="submit" class="rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600 transition">
                                    Approve
                                </button>
                            </form>
                        </div>
                    @endif

                    @if (in_array('settled', $availableTransitions))
                        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Settle Claim</h3>
                            <form method="POST" action="{{ route('admin.claims.settle', $claim) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-content">Settled Amount</label>
                                    <input type="number" name="settled_amount" step="0.01" min="0" value="{{ old('settled_amount', $claim->approved_amount ?? $claim->claimed_amount) }}" required
                                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-content">Resolution Notes</label>
                                    <textarea name="resolution_notes" rows="2" required
                                              class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                                              placeholder="Settlement details...">{{ old('resolution_notes') }}</textarea>
                                </div>
                                <button type="submit" onclick="return confirm('Settle this claim?')"
                                        class="rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600 transition">
                                    Settle Claim
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Communication Timeline --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Communications</h3>
                        </div>

                        @if ($claim->communications->isNotEmpty())
                            <div class="space-y-4 mb-6">
                                @foreach ($claim->communications as $comm)
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div class="w-2.5 h-2.5 rounded-full {{ $comm->direction === 'outgoing' ? 'bg-brand-500' : 'bg-success-500' }} mt-1.5"></div>
                                            @if (! $loop->last)
                                                <div class="w-px flex-1 bg-surface-border"></div>
                                            @endif
                                        </div>
                                        <div class="pb-4 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $comm->direction === 'outgoing' ? 'bg-brand-50 text-brand-600' : 'bg-success-50 text-success-600' }}">
                                                    {{ ucfirst($comm->direction) }}
                                                </span>
                                                <span class="text-xs text-content-muted">via {{ \App\Models\ClaimCommunication::CHANNEL_LABELS[$comm->channel] ?? $comm->channel }}</span>
                                            </div>
                                            @if ($comm->subject)
                                                <p class="text-sm font-medium text-content mt-1">{{ $comm->subject }}</p>
                                            @endif
                                            <p class="text-sm text-content mt-1">{{ $comm->message }}</p>
                                            <p class="text-xs text-content-muted mt-1">
                                                {{ $comm->communicated_at->format('d M Y, h:i A') }}
                                                @if ($comm->communicator)
                                                    by {{ $comm->communicator->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-content-muted mb-4">No communications recorded yet.</p>
                        @endif

                        {{-- Add Communication Form --}}
                        <div class="border-t border-surface-border pt-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-content-secondary mb-3">Add Communication</h4>
                            <form method="POST" action="{{ route('admin.claims.add-communication', $claim) }}" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <select name="direction" required
                                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            <option value="outgoing">Outgoing</option>
                                            <option value="incoming">Incoming</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select name="channel" required
                                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            @foreach (\App\Models\ClaimCommunication::CHANNEL_LABELS as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <input type="text" name="subject" placeholder="Subject (optional)"
                                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                                </div>
                                <div>
                                    <textarea name="message" rows="2" required placeholder="Message..."
                                              class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"></textarea>
                                </div>
                                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                                    Add Communication
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">

                    {{-- Related Return --}}
                    @if ($claim->returnOrder)
                        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Linked Return</h3>
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-content-muted">Return ID</dt>
                                    <dd class="font-mono font-medium">
                                        <a href="{{ route('admin.returns.show', $claim->returnOrder) }}" class="text-brand-500 hover:underline">
                                            RTN-{{ str_pad($claim->returnOrder->id, 5, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </dd>
                                </div>
                                <div><dt class="text-content-muted">Type</dt><dd class="font-medium text-content">{{ $claim->returnOrder->type_label }}</dd></div>
                                <div><dt class="text-content-muted">Status</dt><dd class="font-medium text-content">{{ $claim->returnOrder->status_label }}</dd></div>
                            </dl>
                        </div>
                    @endif

                    {{-- Related Order --}}
                    @if ($claim->order)
                        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Linked Order</h3>
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-content-muted">Order ID</dt>
                                    <dd class="font-mono font-medium">
                                        <a href="{{ route('admin.orders.show', $claim->order) }}" class="text-brand-500 hover:underline">
                                            {{ $claim->order->marketplace_order_id }}
                                        </a>
                                    </dd>
                                </div>
                                <div><dt class="text-content-muted">Customer</dt><dd class="font-medium text-content">{{ $claim->order->customer_name ?? '-' }}</dd></div>
                                <div><dt class="text-content-muted">Amount</dt><dd class="font-medium text-content">Rs. {{ number_format($claim->order->total_amount, 2) }}</dd></div>
                            </dl>
                        </div>
                    @endif

                    {{-- Timestamps --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Timeline</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-content-muted">Created</dt><dd class="font-medium text-content">{{ $claim->created_at->format('d M Y, h:i A') }}</dd></div>
                            <div><dt class="text-content-muted">Filed</dt><dd class="font-medium text-content">{{ $claim->filed_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Resolved</dt><dd class="font-medium text-content">{{ $claim->resolved_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Evidence --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Evidence</h3>
                        @if ($claim->evidence_json && count($claim->evidence_json) > 0)
                            <div class="space-y-2">
                                @foreach ($claim->evidence_json as $evidence)
                                    <div class="flex items-center gap-2 p-2 rounded-lg bg-surface-secondary text-sm text-content">
                                        <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        {{ is_string($evidence) ? $evidence : ($evidence['name'] ?? 'Document') }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-content-muted">No evidence uploaded.</p>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
