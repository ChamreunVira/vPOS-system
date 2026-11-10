<x-layouts.app title="Settings">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-ui.page-header title="Settings" subtitle="How your shop looks, charges and prints.">
            <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
        </x-ui.page-header>

        <x-ui.tabs :tabs="['business' => 'Business profile', 'currency' => 'Currency & VAT', 'receipt' => 'Receipt', 'payments' => 'Payment methods']">
            <x-ui.tab-panel name="business">
                <x-ui.card title="Business profile" class="max-w-3xl">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input class="sm:col-span-2" label="Business name" name="business[name]" :value="$settings['business']['name']" />
                        <x-form.input label="Phone" name="business[phone]" :value="$settings['business']['phone']" />
                        <x-form.input label="Email" name="business[email]" type="email" :value="$settings['business']['email']" />
                        <x-form.input class="sm:col-span-2" label="VAT registration number" name="business[vat_number]" :value="$settings['business']['vat_number']" />
                        <div class="sm:col-span-2"><x-form.textarea label="Address" name="business[address]" rows="2" :value="$settings['business']['address']" /></div>
                        <div class="sm:col-span-2"><x-form.image-upload label="Business logo" name="logo" hint="Square PNG, shown on receipts and the sidebar" /></div>
                    </div>
                </x-ui.card>
            </x-ui.tab-panel>

            <x-ui.tab-panel name="currency">
                <div x-data="{ vat: {{ $settings['currency']['vat_enabled'] ? 'true' : 'false' }} }"><x-ui.card title="Currency & VAT" class="max-w-3xl">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.select label="Primary currency" name="currency[primary]" :options="['USD' => 'US dollar (USD)', 'KHR' => 'Cambodian riel (KHR)']" :value="$settings['currency']['primary']" />
                        <x-form.select label="Show second currency" name="currency[secondary]" :options="['KHR' => 'Cambodian riel (KHR)', 'USD' => 'US dollar (USD)', '' => 'None']" :value="$settings['currency']['secondary']" />
                        <x-form.input label="Exchange rate" name="currency[rate]" type="number" prefix="$1 =" suffix="KHR" :value="$settings['currency']['rate']" />
                        <div class="sm:col-span-2 border-t border-line pt-4"><x-form.toggle name="currency[vat_enabled]" label="Charge VAT" hint="Added to every sale at checkout." :checked="$settings['currency']['vat_enabled']" x-model="vat" /></div>
                        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2" x-show="vat">
                            <x-form.input label="VAT rate" name="currency[vat_rate]" type="number" step="0.1" suffix="%" :value="$settings['currency']['vat_rate']" />
                            <div class="self-end"><x-form.toggle name="currency[prices_include_vat]" label="Prices include VAT" :checked="$settings['currency']['prices_include_vat']" /></div>
                        </div>
                    </div>
                </x-ui.card></div>
            </x-ui.tab-panel>

            <x-ui.tab-panel name="receipt">
                <x-ui.card title="Receipt settings" class="max-w-3xl">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.select label="Paper width" name="receipt[paper]" :options="['58mm', '80mm']" :value="$settings['receipt']['paper']" />
                        <div></div>
                        <div class="sm:col-span-2"><x-form.textarea label="Header message" name="receipt[header]" rows="2" :value="$settings['receipt']['header']" /></div>
                        <div class="sm:col-span-2"><x-form.textarea label="Footer message" name="receipt[footer]" rows="2" :value="$settings['receipt']['footer']" /></div>
                        <div class="sm:col-span-2 space-y-4 border-t border-line pt-4">
                            <x-form.toggle name="receipt[show_logo]" label="Show logo" :checked="$settings['receipt']['show_logo']" />
                            <x-form.toggle name="receipt[show_khr]" label="Show total in riel" :checked="$settings['receipt']['show_khr']" />
                            <x-form.toggle name="receipt[auto_print]" label="Print automatically after payment" :checked="$settings['receipt']['auto_print']" />
                        </div>
                    </div>
                </x-ui.card>
            </x-ui.tab-panel>

            <x-ui.tab-panel name="payments">
                <div class="max-w-3xl space-y-6">
                    <x-ui.card title="Payment methods" description="Choose what cashiers can accept.">
                        <div class="space-y-5 divide-y divide-neutral-100">
                            @foreach ($settings['payments'] as $method)
                                <div class="pt-5 first:pt-0"><x-form.toggle :name="'payments['.$method['key'].']'" :label="$method['label']" :hint="$method['hint']" :checked="$method['enabled']" /></div>
                            @endforeach
                        </div>
                    </x-ui.card>
                    <x-ui.card title="KHQR account" description="Shown to customers when they scan to pay.">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.input label="Account name" name="khqr[account_name]" :value="$settings['khqr']['account_name']" />
                            <x-form.input label="Bakong account ID" name="khqr[bakong_id]" :value="$settings['khqr']['bakong_id']" />
                            <x-form.input label="Merchant city" name="khqr[merchant_city]" :value="$settings['khqr']['merchant_city']" />
                        </div>
                    </x-ui.card>
                </div>
            </x-ui.tab-panel>
        </x-ui.tabs>
    </form>
</x-layouts.app>
