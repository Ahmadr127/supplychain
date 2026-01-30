@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Green Header with Greeting -->
    <div class="bg-gradient-to-r from-emerald-600 to-green-600 rounded-2xl shadow-lg mb-4">
        <div class="px-6 py-6">
            <div class="flex justify-between items-start">
                <div class="text-white">
                    <p class="text-sm opacity-90 mb-1">Selamat Datang Kembali,</p>
                    <h1 class="text-2xl font-bold mb-2 flex items-center gap-2">
                        {{ $user->name }} 👋
                    </h1>
                    <div class="flex gap-2 text-xs">
                        <span class="bg-white/20 px-3 py-1 rounded-full">{{ $user->role->name }}</span>
                        @if($user->departments->isNotEmpty())
                        <span class="bg-white/20 px-3 py-1 rounded-full">{{ $user->departments->first()->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-right text-white">
                    <p class="text-xs opacity-90">{{ \Carbon\Carbon::now()->format('l') }}</p>
                    <p class="text-xl font-bold">{{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @if($myRequestsStats)
            <!-- card my requestssebelumnya yg untuk pending sudah di hapus -->
            <a href="{{ route('approval-requests.my-requests') }}" class="block group">
                <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition border border-gray-100 h-full group-hover:border-indigo-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-100 transition">
                            <i class="fas fa-chart-pie text-indigo-600 text-lg"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">{{ $myRequestsStats['stats']['total'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Total Request</p>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm group-hover:text-indigo-600 transition">My Requests</h3>
                    <div class="pt-3 border-t border-gray-50 space-y-2">
                        <div class="flex justify-between items-center text-[10px] text-gray-500">
                            <span>Ongoing</span>
                            <span class="font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">{{ $myRequestsStats['stats']['on_progress'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-gray-500">
                            <span>Approved</span>
                            <span class="font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">{{ $myRequestsStats['stats']['approved'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if($pendingApprovalsStats)
            <a href="{{ route('approval-requests.pending-approvals') }}" class="block group">
                <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition border border-gray-100 h-full group-hover:border-green-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100 transition">
                            <i class="fas fa-paper-plane text-green-600 text-lg"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">{{ $pendingApprovalsStats['stats']['on_progress'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">On Progress</p>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm group-hover:text-green-600 transition">Pending Approvals</h3>
                    <div class="flex items-center gap-2 mb-3">
                        @if(($pendingApprovalsStats['stats']['pending'] ?? 0) == 0)
                        <span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium border border-green-100">
                            Semua sudah diproses
                        </span>
                        @else
                        <span class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium border border-amber-100">
                            Ada {{ $pendingApprovalsStats['stats']['pending'] }} pending
                        </span>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-gray-50 flex items-center justify-between text-[10px] text-gray-500">
                        <span>Pending: {{ $pendingApprovalsStats['stats']['pending'] ?? 0 }}</span>
                        <span>Hari ini: {{ $pendingApprovalsStats['stats']['approved_today'] ?? 0 }}</span>
                    </div>
                </div>
            </a>
            @endif

            @if($processPurchasingStats)
            <a href="{{ route('reports.approval-requests.process-purchasing') }}" class="block group">
                <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition border border-gray-100 h-full group-hover:border-purple-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center group-hover:bg-purple-100 transition">
                            <i class="fas fa-shopping-cart text-purple-600 text-lg"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">{{ $processPurchasingStats['need_attention'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Total</p>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm group-hover:text-purple-600 transition">Process Purchasing</h3>
                    <div class="flex items-center gap-2 mb-3">
                        @if(($processPurchasingStats['need_attention'] ?? 0) == 0)
                        <span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium border border-green-100">
                            Tidak ada yang perlu diproses
                        </span>
                        @else
                        <span class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium border border-amber-100">
                            {{ $processPurchasingStats['need_attention'] }} perlu perhatian
                        </span>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-gray-50 flex items-center justify-between text-[10px] text-gray-500">
                        <span>Perlu perhatian: {{ $processPurchasingStats['need_attention'] ?? 0 }}</span>
                    </div>
                </div>
            </a>
            @endif

            @if($pendingReleasesStats)
            <a href="{{ route('release-requests.my-pending') }}" class="block group">
                <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition border border-gray-100 h-full group-hover:border-red-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center group-hover:bg-red-100 transition">
                            <i class="fas fa-check-circle text-red-600 text-lg"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">{{ $pendingReleasesStats['stats']['pending'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Total</p>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm group-hover:text-red-600 transition">Pending Releases</h3>
                    <div class="flex items-center gap-2 mb-3">
                        @if(($pendingReleasesStats['stats']['pending'] ?? 0) == 0)
                        <span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium border border-green-100">
                            Tidak ada pending
                        </span>
                        @else
                        <span class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium border border-amber-100">
                            Ada {{ $pendingReleasesStats['stats']['pending'] }} pending
                        </span>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-gray-50 flex items-center justify-between text-[10px] text-gray-500">
                        <span><i class="fas fa-check text-green-600"></i> {{ $pendingReleasesStats['stats']['approved_today'] ?? 0 }}</span>
                        <span><i class="fas fa-times text-red-600"></i> 0</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
            @endif
        </div>
    </div>

    <!-- Quick Actions / Recent Updates -->
    @if($recentUpdates->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                <i class="fas fa-bolt text-amber-500"></i>
                Aksi Cepat
            </h2>
        </div>
        <div class="p-0">
            <div class="divide-y divide-gray-50">
                @foreach($recentUpdates->take(6) as $update)
                <a href="{{ $update['url'] }}" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition group">
                    <div class="w-8 h-8 bg-{{ $update['color'] }}-50 rounded-lg flex items-center justify-center flex-shrink-0 border border-{{ $update['color'] }}-100">
                        <i class="{{ $update['icon'] }} text-{{ $update['color'] }}-600 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 text-xs mb-0.5">{{ $update['title'] }}</h4>
                        <p class="text-[10px] text-gray-500 truncate">{{ $update['description'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($update['timestamp'])->diffForHumans() }}</span>
                        <i class="fas fa-chevron-right text-gray-300 text-xs group-hover:text-gray-500 transition"></i>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Empty State -->
    @if(!$myRequestsStats && !$pendingApprovalsStats && !$processPurchasingStats && !$pendingReleasesStats)
    <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-100">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-50 rounded-full mb-3">
            <i class="fas fa-info-circle text-2xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Selamat Datang di Dashboard</h3>
        <p class="text-sm text-gray-500">Anda belum memiliki tugas aktif atau izin yang ditetapkan.</p>
    </div>
    @endif
</div>
@endsection
