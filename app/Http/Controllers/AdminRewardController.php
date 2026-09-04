<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CountryReward;
use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminRewardController extends Controller
{
    public function index()
    {
        $countries = CountryReward::orderBy('country_name')->get();
        $default = (int) (AppSetting::get('reward_per_listen_default', 100) ?? 100);

        return view('admin.reward-settings', compact('countries', 'default'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_code' => 'required|string|size:2|alpha|unique:country_rewards,country_code',
            'country_name' => 'required|string|max:100',
            'amount_per_listen' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        CountryReward::create([
            'country_code' => strtoupper($request->country_code),
            'country_name' => $request->country_name,
            'amount_per_listen' => (int) $request->amount_per_listen,
            'is_active' => true,
        ]);

        return back()->with('status', 'Country reward rate added.');
    }

    public function update(Request $request, CountryReward $countryReward)
    {
        $validator = Validator::make($request->all(), [
            'country_name' => 'required|string|max:100',
            'amount_per_listen' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $countryReward->update([
            'country_name' => $request->country_name,
            'amount_per_listen' => (int) $request->amount_per_listen,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "{$countryReward->country_name} updated.");
    }

    public function destroy(CountryReward $countryReward)
    {
        $countryReward->delete();

        return back()->with('status', "{$countryReward->country_name} removed.");
    }

    public function default(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reward_per_listen_default' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        AppSetting::updateOrCreate(
            ['key' => 'reward_per_listen_default'],
            ['value' => (int) $request->reward_per_listen_default, 'group' => 'rewards', 'description' => 'Default reward per listen for countries without a configured rate']
        );

        return back()->with('status', 'Default reward rate saved.');
    }

    public function lookup(Request $request, GeoIpService $geo)
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|ip',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ip = $request->ip;
        $countryCode = $geo->countryCode($ip);
        $country = $countryCode ? CountryReward::where('country_code', $countryCode)->first() : null;

        return back()->with(
            'lookup_result',
            [
                'ip' => $ip,
                'country_code' => $countryCode,
                'country_name' => $country?->country_name,
                'amount' => CountryReward::amountFor($countryCode),
                'source' => $country ? 'configured' : 'default',
            ]
        );
    }
}