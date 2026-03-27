<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserSetupController extends Controller
{
    /**
     * Store or replace selected skills during onboarding.
     */
    public function storeSkills(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teach_skill_ids' => ['required', 'array', 'size:3'],
            'teach_skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')],
            'learn_skill_ids' => ['required', 'array', 'size:2'],
            'learn_skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')],
        ]);

        if (count(array_unique($validated['teach_skill_ids'])) !== 3) {
            return response()->json([
                'message' => 'Debes seleccionar exactamente 3 habilidades para ensenar.',
            ], 422);
        }

        if (count(array_unique($validated['learn_skill_ids'])) !== 2) {
            return response()->json([
                'message' => 'Debes seleccionar exactamente 2 intereses para aprender.',
            ], 422);
        }

        $teachIds = collect($validated['teach_skill_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $learnIds = collect($validated['learn_skill_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $teachIds->contains($id))
            ->values();

        $user = $request->user();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $teachIds, $learnIds, $now): void {
            $user->skills()->detach();

            if ($teachIds->isNotEmpty()) {
                $user->skills()->syncWithPivotValues($teachIds->all(), ['type' => 'teach'], false);
            }

            if ($learnIds->isNotEmpty()) {
                $user->skills()->syncWithPivotValues($learnIds->all(), ['type' => 'learn'], false);
            }

            $user->forceFill(['skills_onboarding_completed_at' => $now])->save();
        });

        return response()->json([
            'message' => 'Tus habilidades se guardaron correctamente.',
        ]);
    }

    /**
     * Store or replace selected availabilities during onboarding.
     */
    public function storeAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*' => ['string'],
        ]);

        $parsedBlocks = $this->parseBlocks($validated['blocks']);

        if (count($parsedBlocks) === 0) {
            return response()->json([
                'message' => 'Selecciona al menos un bloque horario valido.',
            ], 422);
        }

        $user = $request->user();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $parsedBlocks, $now): void {
            $user->availabilities()->delete();
            $user->availabilities()->createMany($parsedBlocks);
            $user->forceFill(['availability_onboarding_completed_at' => $now])->save();
        });

        return response()->json([
            'message' => 'Tu disponibilidad se guardo correctamente.',
        ]);
    }

    /**
     * Add a single skill from profile settings.
     */
    public function addSkill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skill_id' => ['required', 'integer', Rule::exists('skills', 'id')],
            'type' => ['required', Rule::in(['teach', 'learn'])],
        ]);

        $user = $request->user();
        $isTeach = $validated['type'] === 'teach';

        DB::transaction(function () use ($user, $validated, $isTeach): void {
            $primaryRelation = $isTeach ? $user->taughtSkills() : $user->learningSkills();
            $oppositeRelation = $isTeach ? $user->learningSkills() : $user->taughtSkills();

            $oppositeRelation->detach($validated['skill_id']);
            $primaryRelation->syncWithPivotValues([$validated['skill_id']], ['type' => $validated['type']], false);
        });

        return response()->json(['message' => 'Habilidad agregada.']);
    }

    /**
     * Remove a single skill from profile settings.
     */
    public function removeSkill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skill_id' => ['required', 'integer', Rule::exists('skills', 'id')],
            'type' => ['required', Rule::in(['teach', 'learn'])],
        ]);

        $relation = $validated['type'] === 'teach'
            ? $request->user()->taughtSkills()
            : $request->user()->learningSkills();

        $relation->detach($validated['skill_id']);

        return response()->json(['message' => 'Habilidad eliminada.']);
    }

    /**
     * Add a single availability block from profile settings.
     */
    public function addAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'block' => ['required', 'string'],
        ]);

        $parsed = $this->parseBlocks([$validated['block']]);

        if (count($parsed) !== 1) {
            return response()->json([
                'message' => 'Selecciona un bloque horario valido.',
            ], 422);
        }

        $request->user()->availabilities()->firstOrCreate($parsed[0]);

        return response()->json(['message' => 'Bloque horario agregado.']);
    }

    /**
     * Remove a single availability block from profile settings.
     */
    public function removeAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'block' => ['required', 'string'],
        ]);

        $parsed = $this->parseBlocks([$validated['block']]);

        if (count($parsed) !== 1) {
            return response()->json([
                'message' => 'Bloque horario invalido.',
            ], 422);
        }

        $request->user()->availabilities()
            ->where('weekday', $parsed[0]['weekday'])
            ->where('time_block', $parsed[0]['time_block'])
            ->delete();

        return response()->json(['message' => 'Bloque horario eliminado.']);
    }

    /**
     * Parse and validate block keys in format weekday|time_block.
     *
     * @param  array<int, string>  $blocks
     * @return array<int, array{weekday: string, time_block: string}>
     */
    private function parseBlocks(array $blocks): array
    {
        $allowedDays = array_keys(self::availabilityDays());
        $allowedBlocks = self::availabilityBlocks();
        $parsed = [];

        foreach ($blocks as $block) {
            [$weekday, $timeBlock] = array_pad(explode('|', $block, 2), 2, null);

            if (!is_string($weekday) || !is_string($timeBlock)) {
                continue;
            }

            if (!in_array($weekday, $allowedDays, true) || !in_array($timeBlock, $allowedBlocks, true)) {
                continue;
            }

            $parsed[$weekday . '|' . $timeBlock] = [
                'weekday' => $weekday,
                'time_block' => $timeBlock,
            ];
        }

        return array_values($parsed);
    }

    /**
     * Availability day labels used by views and validation.
     *
     * @return array<string, string>
     */
    public static function availabilityDays(): array
    {
        return [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miercoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
        ];
    }

    /**
     * Availability time blocks used by views and validation.
     *
     * @return array<int, string>
     */
    public static function availabilityBlocks(): array
    {
        return [
            '7-8 AM',
            '9-10 AM',
            '11-12 AM',
            '12-1 PM',
            '1-2 PM',
            '2-3 PM',
            '4-5 PM',
            '6-7 PM',
        ];
    }
}
