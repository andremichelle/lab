package
{
	public class PulseQuad
	{
		public var morph: Number; // between 0 and 1
		public var pulse: Number; // between 0 and 1
		
		public function PulseQuad()
		{
			morph = 0;
			pulse = .5;
		}
		
		//-- phase is between 0 and 1
		//-- phase += frequency / samplingRate
		//-- getAmp(phase-int(phase)) 
		public function getAmp( phase: Number ): Number
		{
			var a: Number;
			var b: Number;

			if( pulse < .5 )
				a = morph * pulse / 2;
			else
				a = morph * ( 1 - pulse ) / 2;
			
			if( phase < pulse )
			{
				if( phase < a )
				{
					b = phase / a - 1;
					return 1 - b * b;
				}
				
				if( phase < pulse - a )
					return 1;
				
				b = ( phase - pulse + a ) / a;
				return 1 - b * b;
			}
			
			if( phase < pulse + a )
			{
				b = ( phase - pulse ) / a - 1;
				return b * b - 1;
			}
			
			if( phase <= 1 - a )
				return -1;

			b = ( phase - 1 + a ) / a;
			return b * b - 1;
		}
	}
}