import {test} from 'node:test';
import assert from 'node:assert/strict';
import {distanceKm,compareKey,formatPrice} from '../../resources/js/Components/Marketplace/comparison.mjs';
test('distance handles missing coordinates without treating them as zero',()=>{
 for(const value of ['',null,undefined,'not-a-number'])assert.equal(distanceKm(value,124,12,124),null);
 assert.equal(distanceKm(95,124,12,124),null);
 assert.equal(distanceKm(0,0,0,0),0);
 assert.ok(Math.abs(distanceKm(0,0,0,1)-111.195)<.01);
});
test('comparison distinguishes services and preserves zero prices',()=>{
 assert.notEqual(compareKey({service:'spaces',id:'1'}),compareKey({service:'haul',id:'1'}));
 assert.match(formatPrice(0),/0/);
 assert.match(formatPrice(1250),/1,250/);
});
