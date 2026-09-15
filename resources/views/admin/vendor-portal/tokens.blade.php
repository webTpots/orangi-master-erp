<x-admin-layout>
    <x-slot:title>Vendor Portal Tokens</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-display font-semibold text-content">Vendor Portal Tokens</h1>
            <p class="text-sm text-content-secondary mt-0.5">Manage vendor portal access tokens</p>
        </div>
    </div>

    {{-- Generate Token Form --}}
    <div class="bg-white rounded-xl border border-surface-border p-5 mb-6">
        <h2 class="text-sm font-semibold text-content mb-4">Generate New Token</h2>
        <form method="POST" action="{{ route('admin.vendor-portal.generate-token') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="vendor_id" class="block text-xs font-medium text-content-secondary mb-1">Vendor</label>
                <select name="vendor_id" id="vendor_id" required class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label for="email" class="block text-xs font-medium text-content-secondary mb-1">Vendor Email</label>
                <input type="email" name="email" id="email" required placeholder="vendor@example.com" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
            </div>
            <button type="submit" class="bg-brand-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-brand-600 transition-colors whitespace-nowrap">
                Generate Token
            </button>
        </form>
    </div>

    {{-- Tokens List --}}
    <div class="bg-white rounded-xl border border-surface-border">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border-light">
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Vendor</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Token</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Last Used</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Expires</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse($tokens as $token)
                        <tr class="hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-5 py-3 font-medium text-content">{{ $token->vendor->name }}</td>
                            <td class="px-5 py-3 text-content-secondary">{{ $token->email }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2" x-data="{ copied: false }">
                                    <code class="text-xs bg-surface-secondary px-2 py-1 rounded font-mono">{{ Str::limit($token->token, 20) }}</code>
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $token->token }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="text-content-muted hover:text-content transition-colors"
                                        :title="copied ? 'Copied!' : 'Copy token'"
                                    >
                                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied" x-cloak class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-content-secondary text-xs">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3 text-content-secondary text-xs">{{ $token->expires_at?->format('d M Y') ?? 'Never' }}</td>
                            <td class="px-5 py-3">
                                @if($token->isValid())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-success-50 text-success-600">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-danger-50 text-danger-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($token->is_active)
                                    <form method="POST" action="{{ route('admin.vendor-portal.revoke-token', $token) }}" class="inline" onsubmit="return confirm('Revoke this token?')">
                                        @csrf
                                        <button type="submit" class="text-xs text-danger-500 hover:text-danger-600 font-medium">Revoke</button>
                                    </form>
                                @else
                                    <span class="text-xs text-content-muted">Revoked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-content-muted text-sm">No tokens generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tokens->hasPages())
            <div class="px-5 py-3 border-t border-surface-border-light">
                {{ $tokens->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
