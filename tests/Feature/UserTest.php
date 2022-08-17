<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DTApi\Models\Company;
use DTApi\Models\Department;
use DTApi\Models\Type;
use DTApi\Models\UsersBlacklist;
use DTApi\Models\User;
use DTApi\Models\Town;
use DTApi\Models\UserMeta;
use DTApi\Models\UserTowns;
use DTApi\Models\UserLanguages;
use Illuminate\Support\Facades\DB;


class UserTest extends TestCase
{
    /**
     *  test to see if user is created or updated successfully.
     *
     * @return void
     * @test
     */
    public function user_is_created_updated_successfully()
    {
        $data = [
            'role'                  => '1',
            'name'                  => 'testuser',
            'company_id'            => '2',
            'department_id'         => '2',
            'email'                 => 'abc@email.com',
            'dob_or_orgid'          => '2',
            'phone'                 => '923158576542',
            'mobile'                => '923158576542',
            'password'              => '12345678',
            'customer_type'         => '5',
            'username'              => 'test-user',
            'post_code'             => '75487',
            'city'                  => 'karachi',
            'town'                  => 'landhi',
            'country'               => 'pakistan',
            'reference'             => 'testrefernce',
            'additional_info'       => 'no',
            'cost_place'            => '152',
            'fee'                   => '25.20',
            'time_to_charge'        => '12:00:00',
            'time_to_pay'           => '14:00:00',
            'charge_ob'             => '5',
            'customer_id'           => '1',
            'charge_km'             => '2',
            'maximum_km'            => '2',
            'translator_ex'         => '5',
            'worked_for'            => 'test',
            'address'               => 'abc xyz',
            'address_2'             => 'abc xyz',
            'translator_type'       => 'english',
            'organization_number'   => '45',
            'gender'                => 'male',
            'translator_level'      => 'no',
            'user_language'         => 'english',
            'new_towns'             => 'no',
            'user_towns_projects'   => 'no',
            'status'                => '1',
        ];
        $id = 1;

        $model = is_null($id) ? new User : User::findOrFail($id);
        $model->user_type = $data['role'];
        $model->name = $data['name'];
        $model->company_id = $data['company_id'] != '' ? $data['company_id'] : 0;
        $model->department_id = $data['department_id'] != '' ? $data['department_id'] : 0;
        $model->email = $data['email'];
        $model->dob_or_orgid = $data['dob_or_orgid'];
        $model->phone = $data['phone'];
        $model->mobile = $data['mobile'];


        if (!$id || $id && $data['password']) $model->password = bcrypt($data['password']);
        $model->detachAllRoles();
        $model->save();
        $model->attachRole($data['role']);
        $data = array();

        if ($data['role'] == env('CUSTOMER_ROLE_ID')) {

            if($data['consumer_type'] == 'paid')
            {
                if($data['company_id'] == '')
                {
                    $type = Type::where('code', 'paid')->first();
                    $company = Company::create(['name' => $data['name'], 'type_id' => $type->id, 'additional_info' => 'Created automatically for user ' . $model->id]);
                    $department = Department::create(['name' => $data['name'], 'company_id' => $company->id, 'additional_info' => 'Created automatically for user ' . $model->id]);

                    $model->company_id = $company->id;
                    $model->department_id = $department->id;
                    $model->save();
                }
            }

            $user_meta = UserMeta::firstOrCreate(['user_id' => $model->id]);
            $old_meta = $user_meta->toArray();
            $user_meta->consumer_type = $data['consumer_type'];
            $user_meta->customer_type = $data['customer_type'];
            $user_meta->username = $data['username'];
            $user_meta->post_code = $data['post_code'];
            $user_meta->address = $data['address'];
            $user_meta->city = $data['city'];
            $user_meta->town = $data['town'];
            $user_meta->country = $data['country'];
            $user_meta->reference = (isset($data['reference']) && $data['reference'] == 'yes') ? '1' : '0';
            $user_meta->additional_info = $data['additional_info'];
            $user_meta->cost_place = isset($data['cost_place']) ? $data['cost_place'] : '';
            $user_meta->fee = isset($data['fee']) ? $data['fee'] : '';
            $user_meta->time_to_charge = isset($data['time_to_charge']) ? $data['time_to_charge'] : '';
            $user_meta->time_to_pay = isset($data['time_to_pay']) ? $data['time_to_pay'] : '';
            $user_meta->charge_ob = isset($data['charge_ob']) ? $data['charge_ob'] : '';
            $user_meta->customer_id = isset($data['customer_id']) ? $data['customer_id'] : '';
            $user_meta->charge_km = isset($data['charge_km']) ? $data['charge_km'] : '';
            $user_meta->maximum_km = isset($data['maximum_km']) ? $data['maximum_km'] : '';
            $user_meta->save();
            $new_meta = $user_meta->toArray();

            $blacklistUpdated = [];
            $userBlacklist = UsersBlacklist::where('user_id', $id)->get();
            $userTranslId = collect($userBlacklist)->pluck('translator_id')->all();

            $diff = null;
            if ($data['translator_ex']) {
                $diff = array_intersect($userTranslId, $data['translator_ex']);
            }
            if ($diff || $data['translator_ex']) {
                foreach ($data['translator_ex'] as $translatorId) {
                    $blacklist = new UsersBlacklist();
                    if ($model->id) {
                        $already_exist = UsersBlacklist::translatorExist($model->id, $translatorId);
                        if ($already_exist == 0) {
                            $blacklist->user_id = $model->id;
                            $blacklist->translator_id = $translatorId;
                            $blacklist->save();
                        }
                        $blacklistUpdated [] = $translatorId;
                    }

                }
                if ($blacklistUpdated) {
                    UsersBlacklist::deleteFromBlacklist($model->id, $blacklistUpdated);
                }
            } else {
                UsersBlacklist::where('user_id', $model->id)->delete();
            }


        } else if ($data['role'] == env('TRANSLATOR_ROLE_ID')) {

            $user_meta = UserMeta::firstOrCreate(['user_id' => $model->id]);

            $user_meta->translator_type = $data['translator_type'];
            $user_meta->worked_for = $data['worked_for'];
            if ($data['worked_for'] == 'yes') {
                $user_meta->organization_number = $data['organization_number'];
            }
            $user_meta->gender = $data['gender'];
            $user_meta->translator_level = $data['translator_level'];
            $user_meta->additional_info = $data['additional_info'];
            $user_meta->post_code = $data['post_code'];
            $user_meta->address = $data['address'];
            $user_meta->address_2 = $data['address_2'];
            $user_meta->town = $data['town'];
            $user_meta->save();

            $data['translator_type'] = $data['translator_type'];
            $data['worked_for'] = $data['worked_for'];
            if ($data['worked_for'] == 'yes') {
                $data['organization_number'] = $data['organization_number'];
            }
            $data['gender'] = $data['gender'];
            $data['translator_level'] = $data['translator_level'];

            $langidUpdated = [];
            if ($data['user_language']) {
                foreach ($data['user_language'] as $langId) {
                    $userLang = new UserLanguages();
                    $already_exit = $userLang::langExist($model->id, $langId);
                    if ($already_exit == 0) {
                        $userLang->user_id = $model->id;
                        $userLang->lang_id = $langId;
                        $userLang->save();
                    }
                    $langidUpdated[] = $langId;

                }
                if ($langidUpdated) {
                    $userLang::deleteLang($model->id, $langidUpdated);
                }
            }

        }

        if ($data['new_towns']) {

            $towns = new Town;
            $towns->townname = $data['new_towns'];
            $towns->save();
            $newTownsId = $towns->id;
        }

        $townidUpdated = [];
        if ($data['user_towns_projects']) {
            $del = DB::table('user_towns')->where('user_id', '=', $model->id)->delete();
            foreach ($data['user_towns_projects'] as $townId) {
                $userTown = new UserTowns();
                $already_exit = $userTown::townExist($model->id, $townId);
                if ($already_exit == 0) {
                    $userTown->user_id = $model->id;
                    $userTown->town_id = $townId;
                    $userTown->save();
                }
                $townidUpdated[] = $townId;

            }
        }

        if ($data['status'] == '1') {
            if ($model->status != '1') {
                $this->enable($model->id);
            }
        } else {
            if ($model->status != '0') {
                $this->disable($model->id);
            }
        }
        return $model ? $model : false;
        if($model){
            $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => true,
                ]);
        }else{
            $response
            ->assertStatus(302)
            ->assertJson([
                'status' => false,
            ]);
        }
    }
}
