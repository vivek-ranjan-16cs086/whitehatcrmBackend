<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $teams = Team::with(['project:id,name', 'teamLead:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('teamLead', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.teams.index', [
            'teams' => $teams, 'search' => $search,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:projects,id',
            'team_lead_id' => 'required|integer|exists:users,id',
        ]);
    }

    public function store(Request $request)
    {
        Team::create($this->validated($request));
        return redirect()->route('admin.teams.index')->with('success', 'Team created successfully.');
    }

    public function update(Request $request, Team $team)
    {
        $team->update($this->validated($request));
        return redirect()->route('admin.teams.index')->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('admin.teams.index')->with('success', 'Team deleted successfully.');
    }
}
