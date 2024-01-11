export const STATUS_PENDING = 'pending'

export const STATUS_STARTED = 'started'

export const STATUS_FINISHED = 'finished'

export const PARTY_STATUS = [
    STATUS_PENDING,
    STATUS_STARTED,
    STATUS_FINISHED
]

export const CODE_TYPE_JOIN_CODE = 'join_code'

export const CODE_TYPE_PARTY_ID = 'party_id'

export const CODE_TYPES = [
    CODE_TYPE_JOIN_CODE,
    CODE_TYPE_PARTY_ID
]

export const HAND_MAX_SIZE = 5

export const MOVE_TYPE_HAND = 'hand'

export const MOVE_TYPE_DECK = 'deck'

export const MOVE_TYPE_PARTY_STACK = 'party_stack'

export const MOVE_TYPE_PLAYER_CARD_DRAW = 'player_card_draw'

export const MOVE_TYPES = [
    MOVE_TYPE_HAND,
    MOVE_TYPE_DECK,
    MOVE_TYPE_PARTY_STACK,
    MOVE_TYPE_PLAYER_CARD_DRAW,
]

export default {
  STATUS_PENDING,
  STATUS_STARTED,
  STATUS_FINISHED,
  PARTY_STATUS,
  CODE_TYPE_JOIN_CODE,
  CODE_TYPE_PARTY_ID,
  CODE_TYPES,
  HAND_MAX_SIZE,
  MOVE_TYPE_HAND,
  MOVE_TYPE_DECK,
  MOVE_TYPE_PARTY_STACK,
  MOVE_TYPE_PLAYER_CARD_DRAW,
  MOVE_TYPES
}
