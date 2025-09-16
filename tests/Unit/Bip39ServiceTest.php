<?php

use Roberts\LaravelWallets\Services\Bip39Service;

describe('Bip39Service', function () {
    beforeEach(function () {
        $this->service = new Bip39Service;
    });

    it('creates correct seed from known mnemonic', function () {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $expectedSeed = '5eb00bbddcf069084889a8ab9155568165f5c453ccb85e70811aaed6f6da5fc19a5ac40b389cd370d086206dec8aa6c43daea6690f20ad3d8d48b2d2ce9e38e4';

        $seed = $this->service->mnemonicToSeed($mnemonic, '');

        expect(bin2hex($seed))->toBe($expectedSeed);
    });

    it('creates correct seed from known mnemonic with passphrase', function () {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $expectedSeed = 'c55257c360c07c72029aebc1b53c05ed0362ada38ead3e3e9efa3708e53495531f09a6987599d18264c1e1c92f2cf141630c7a3c4ab7c81b2f001698e7463b04';

        $seed = $this->service->mnemonicToSeed($mnemonic, 'TREZOR');

        expect(bin2hex($seed))->toBe($expectedSeed);
    });

    it('generates 12-word mnemonic', function () {
        $mnemonic = $this->service->generateMnemonic(128);

        expect(explode(' ', $mnemonic))->toHaveCount(12);
    });

    it('generates 24-word mnemonic', function () {
        $mnemonic = $this->service->generateMnemonic(256);

        expect(explode(' ', $mnemonic))->toHaveCount(24);
    });
});
