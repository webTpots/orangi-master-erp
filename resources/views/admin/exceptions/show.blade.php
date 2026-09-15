<x-admin-layout>
    <x-slot name="title">Exception #{{ $exception->id }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.exceptions.index') }}" class="text-content-muted hover:text-content transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h2 class="text-xl font-display font-semibold text-content">
                    Exception <span class="font-mono text-base">EXC-{{ str_pad($exception->id, 5, '0', STR_PAD_LEFT) }}</span>
                </h2>
                @php
                    $severityColors = [
                        'critical' => 'bg-danger-50 text-danger-600',
                        'high'     => 'bg-warning-50 text-warning-700',
                        'medium'   => 'bg-yellow-50 text-yellow-700',
                        'low'      => 'bg-blue-50 text-blue-600',
                    ];
                    $statusColors = [
                        'open'      => 'bg-danger-50 text-danger-600',
                        'in_review' => 'bg-warning-50 text-warning-600',
                        'resolved'  => 'bg-success-50 text-success-600',
                        'ignored'   => 'bg-neutral-50 text-neutral-500',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $severityColors[$exception->severity] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ ucfirst($exception->severity) }}
                </span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$exception->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ \App\Models\AppException::STATUS_LABELS[$exception->status] ?? ucfirst($exception->status) }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if (in_array($exception->status, ['open', 'in_review']))
                    <button onclick="document.getElementById('escalateModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-warning-500 text-warning-600 hover:bg-warning-50 px-3 py-1.5 text-sm font-medium transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Escalate
                    </button>
                    <button onclick="document.getElementById('resolveModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-success-500 text-white hover:bg-success-600 px-3 py-1.5 text-sm font-medium transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Resolve
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main content (2 cols) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Description --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <h3 class="text-sm font-semibold text-content mb-3">{{ $exception->title }}</h3>
                @if ($exception->description)
                    <p class="text-sm text-content-secondary leading-relaxed">{{ $exception->description }}</p>
                @else
                    <p class="text-sm text-content-muted italic">No description provided.</p>
                @endif

                @if ($exception->entity_type)
                    <div class="mt-4 pt-4 border-t border-surface-border-light">
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Related Entity</p>
                        <p class="text-sm text-content">
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $exception->entity_type)) }}</span>
                            @if ($exception->entity_id)
                                <span class="text-content-muted">#{{ $exception->entity_id }}</span>
                            @endif
                        </p>
                    </div>
                @endif

                @if ($exception->category)
                    <div class="mt-4 pt-4 border-t border-surface-border-light">
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Category</p>
                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600">
                            {{ $exception->category->name }}
                        </span>
                        @if ($exception->category->sla_hours)
                            <span class="ml-2 text-xs text-content-muted">SLA: {{ $exception->category->sla_hours }}h</span>
                        @endif
                    </div>
                @endif

                @if ($exception->resolution)
                    <div class="mt-4 pt-4 border-t border-surface-border-light">
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Resolution</p>
                        <p class="text-sm text-content-secondary">{{ $exception->resolution }}</p>
                        @if ($exception->resolver)
                            <p class="text-xs text-content-muted mt-1">
                                Resolved by {{ $exception->resolver->name }} on {{ $exception->resolved_at?->format('d M Y, H:i') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Timeline & Comments --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <h3 class="text-sm font-semibold text-content mb-4">Activity Timeline</h3>

                <div class="space-y-4">
                    {{-- Assignments --}}
                    @foreach ($exception->assignments->sortByDesc('created_at') as $assignment)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-content">
                                    Assigned to <span class="font-medium">{{ $assignment->assignedUser?->name ?? 'Unknown' }}</span>
                                    @if ($assignment->assigner)
                                        by {{ $assignment->assigner->name }}
                                    @endif
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-content-muted">{{ $assignment->created_at->diffForHumans() }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-surface-secondary text-content-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-surface-secondary text-content-secondary">
                                        {{ ucfirst($assignment->priority) }}
                                    </span>
                                </div>
                                @if ($assignment->notes)
                                    <p class="text-xs text-content-muted mt-1">{{ $assignment->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Comments --}}
                    @foreach ($exception->comments as $comment)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $comment->is_internal ? 'bg-yellow-50' : 'bg-surface-secondary' }} flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $comment->is_internal ? 'text-yellow-500' : 'text-content-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-content">{{ $comment->user?->name ?? 'System' }}</p>
                                    @if ($comment->is_internal)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-yellow-50 text-yellow-600">Internal</span>
                                    @endif
                                </div>
                                <p class="text-sm text-content-secondary mt-0.5">{{ $comment->comment }}</p>
                                <span class="text-xs text-content-muted">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach

                    @if ($exception->assignments->isEmpty() && $exception->comments->isEmpty())
                        <p class="text-sm text-content-muted text-center py-4">No activity yet.</p>
                    @endif
                </div>

                {{-- Add Comment Form --}}
                <div class="mt-6 pt-4 border-t border-surface-border-light">
                    <form method="POST" action="{{ route('admin.exceptions.add-comment', $exception) }}">
                        @csrf
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Add Comment</label>
                        <textarea name="comment" rows="3" required placeholder="Write a comment..."
                                  class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"></textarea>
                        <div class="flex items-center justify-between mt-2">
                            <label class="flex items-center gap-2 text-xs text-content-secondary cursor-pointer">
                                <input type="checkbox" name="is_internal" value="1"
                                       class="rounded border-surface-border text-brand-500 focus:ring-brand-400">
                                Internal note (not visible to external users)
                            </label>
                            <button type="submit"
                                    class="rounded-lg bg-brand-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                                Add Comment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar (1 col) --}}
        <div class="space-y-6">
            {{-- SLA Status --}}
            @if ($exception->category && $exception->category->sla_hours && in_array($exception->status, ['open', 'in_review']))
                @php $slaHours = $exception->slaRemainingHours(); @endphp
                <div class="bg-white rounded-xl border border-surface-border-light p-5">
                    <h3 class="text-sm font-semibold text-content mb-3">SLA Status</h3>
                    @if ($slaHours < 0)
                        <div class="flex items-center gap-2 text-danger-500">
                            <span class="w-3 h-3 rounded-full bg-danger-500 animate-pulse"></span>
                            <span class="text-sm font-medium">SLA Breached</span>
                        </div>
                        <p class="text-xs text-content-muted mt-1">Overdue by {{ abs(round($slaHours)) }} hours</p>
                    @elseif ($slaHours <= 4)
                        <div class="flex items-center gap-2 text-warning-500">
                            <span class="w-3 h-3 rounded-full bg-warning-500"></span>
                            <span class="text-sm font-medium">{{ round($slaHours, 1) }}h remaining</span>
                        </div>
                        <p class="text-xs text-content-muted mt-1">Due by {{ $exception->created_at->addHours($exception->category->sla_hours)->format('d M Y, H:i') }}</p>
                    @else
                        <div class="flex items-center gap-2 text-success-500">
                            <span class="w-3 h-3 rounded-full bg-success-500"></span>
                            <span class="text-sm font-medium">{{ round($slaHours, 1) }}h remaining</span>
                        </div>
                        <p class="text-xs text-content-muted mt-1">Due by {{ $exception->created_at->addHours($exception->category->sla_hours)->format('d M Y, H:i') }}</p>
                    @endif
                </div>
            @endif

            {{-- Assignment --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <h3 class="text-sm font-semibold text-content mb-3">Assignment</h3>
                @if ($exception->assignee)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 flex items-center justify-center">
                            <span class="text-brand-600 text-xs font-semibold">{{ substr($exception->assignee->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-content">{{ $exception->assignee->name }}</p>
                            <p class="text-xs text-content-muted">{{ $exception->assignee->role }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-content-muted mb-3">Not assigned</p>
                @endif

                @if (in_array($exception->status, ['open', 'in_review']))
                    <form method="POST" action="{{ route('admin.exceptions.assign', $exception) }}">
                        @csrf
                        <label class="mb-1 block text-xs font-medium text-content-secondary">{{ $exception->assignee ? 'Reassign to' : 'Assign to' }}</label>
                        <select name="assigned_to" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400 mb-2">
                            <option value="">Select user...</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="w-full rounded-lg bg-brand-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                            {{ $exception->assignee ? 'Reassign' : 'Assign' }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Details --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <h3 class="text-sm font-semibold text-content mb-3">Details</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-content-secondary uppercase tracking-wider">Type</dt>
                        <dd class="text-content mt-0.5">{{ str_replace('_', ' ', ucfirst($exception->exception_type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-content-secondary uppercase tracking-wider">Severity</dt>
                        <dd class="mt-0.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $severityColors[$exception->severity] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ ucfirst($exception->severity) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-content-secondary uppercase tracking-wider">Created</dt>
                        <dd class="text-content mt-0.5">{{ $exception->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @if ($exception->resolved_at)
                        <div>
                            <dt class="text-xs font-medium text-content-secondary uppercase tracking-wider">Resolved</dt>
                            <dd class="text-content mt-0.5">{{ $exception->resolved_at->format('d M Y, H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Escalate Modal --}}
    <div id="escalateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-semibold text-content mb-4">Escalate Exception</h3>
            <form method="POST" action="{{ route('admin.exceptions.escalate', $exception) }}">
                @csrf
                <label class="mb-1 block text-xs font-medium text-content-secondary">Reason for Escalation</label>
                <textarea name="reason" rows="3" required placeholder="Why is this being escalated?"
                          class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="document.getElementById('escalateModal').classList.add('hidden')"
                            class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-warning-500 px-4 py-2 text-sm font-medium text-white hover:bg-warning-600 transition-colors">
                        Escalate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Resolve Modal --}}
    <div id="resolveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-semibold text-content mb-4">Resolve Exception</h3>
            <form method="POST" action="{{ route('admin.exceptions.resolve', $exception) }}">
                @csrf
                <label class="mb-1 block text-xs font-medium text-content-secondary">Resolution Notes</label>
                <textarea name="resolution_notes" rows="3" required placeholder="Describe how this was resolved..."
                          class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')"
                            class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600 transition-colors">
                        Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
